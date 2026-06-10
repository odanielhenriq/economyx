<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CategoryBudget;
use App\Models\CreditCard;
use App\Models\RecurringTransaction;
use App\Models\Transaction;
use App\Models\TransactionInstallment;
use App\Services\FinancialAlertService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class ExportDataController extends Controller
{
    public function __construct(
        private FinancialAlertService $financialAlerts
    ) {}

    public function json(): JsonResponse
    {
        $user         = auth()->user();
        $networkIds   = $user->networkUsers()->pluck('id')->all();
        $now          = now();
        $currentMonth = $now->format('Y-m');
        $sixMonthsAgo = $now->copy()->subMonths(6)->startOfMonth();

        $data = [
            'periodo_analise'             => $currentMonth,
            'usuario'                     => $user->name,
            'data_export'                 => $now->toIso8601String(),
            'resumo_periodo'              => $this->getResumoMesAtual($networkIds),
            'cartoes_credito'             => $this->getCartoes($user, $networkIds),
            'contas_fixas'                => $this->getContasFixas($user, $networkIds),
            'compras_parceladas'          => $this->getComprasParceladas($networkIds),
            'todas_transacoes_parceladas' => $this->todasTransacoesParceladas($networkIds),
            'despesas_por_categoria'      => $this->getDespesasPorCategoria($user, $networkIds),
            'resumo_financeiro_6_meses'   => $this->getResumo6Meses($networkIds, $sixMonthsAgo),
            'alertas'                     => $this->financialAlerts->toExportLegacy(
                $this->financialAlerts->collect((int) $now->year, (int) $now->month, $user)
            ),
        ];

        return response()->json($data)
            ->header('Content-Disposition', 'attachment; filename=economyx-' . $currentMonth . '.json');
    }

    // -------------------------------------------------------------------------
    // Resumo do mês atual
    // Receita: Transaction type=rc com due_date no mês
    // Despesa: Transaction type=dc NÃO parcelada + TransactionInstallment do mês
    // Sem dupla contagem: parceladas (installment_total != null) só entram via
    // TransactionInstallment, cujo parcela#1 já representa o 1º vencimento.
    // -------------------------------------------------------------------------
    private function getResumoMesAtual(array $networkIds): array
    {
        $hoje      = now();
        $start     = $hoje->copy()->startOfMonth()->toDateString();
        $end       = $hoje->copy()->endOfMonth()->toDateString();
        $prevStart = $hoje->copy()->subMonthNoOverflow()->startOfMonth()->toDateString();
        $prevEnd   = $hoje->copy()->subMonthNoOverflow()->endOfMonth()->toDateString();

        $receita = (float) Transaction::whereBetween('due_date', [$start, $end])
            ->whereHas('users', fn($q) => $q->whereIn('users.id', $networkIds))
            ->whereHas('type', fn($q) => $q->where('slug', 'rc'))
            ->sum('amount');

        $despesaDireta = (float) Transaction::whereBetween('due_date', [$start, $end])
            ->whereHas('users', fn($q) => $q->whereIn('users.id', $networkIds))
            ->whereHas('type', fn($q) => $q->where('slug', 'dc'))
            ->whereNull('installment_total')
            ->sum('amount');

        $despesaParcelada = (float) TransactionInstallment::whereHas('transaction', function ($q) use ($networkIds) {
                $q->whereHas('users', fn($q2) => $q2->whereIn('users.id', $networkIds));
            })
            ->whereBetween('due_date', [$start, $end])
            ->sum('amount');

        $despesa = $despesaDireta + $despesaParcelada;

        $receitaAnt = (float) Transaction::whereBetween('due_date', [$prevStart, $prevEnd])
            ->whereHas('users', fn($q) => $q->whereIn('users.id', $networkIds))
            ->whereHas('type', fn($q) => $q->where('slug', 'rc'))
            ->sum('amount');

        $despesaDiretaAnt = (float) Transaction::whereBetween('due_date', [$prevStart, $prevEnd])
            ->whereHas('users', fn($q) => $q->whereIn('users.id', $networkIds))
            ->whereHas('type', fn($q) => $q->where('slug', 'dc'))
            ->whereNull('installment_total')
            ->sum('amount');

        $despesaParceladaAnt = (float) TransactionInstallment::whereHas('transaction', function ($q) use ($networkIds) {
                $q->whereHas('users', fn($q2) => $q2->whereIn('users.id', $networkIds));
            })
            ->whereBetween('due_date', [$prevStart, $prevEnd])
            ->sum('amount');

        $despesaAnt = $despesaDiretaAnt + $despesaParceladaAnt;
        $saldo      = $receita - $despesa;
        $saldoAnt   = $receitaAnt - $despesaAnt;

        return [
            'receita_total' => round($receita, 2),
            'despesa_total' => round($despesa, 2),
            'saldo_liquido' => round($saldo, 2),
            'variacao_vs_mes_anterior' => [
                'receita' => $receitaAnt > 0 ? round((($receita - $receitaAnt) / $receitaAnt) * 100, 1) : 0,
                'despesa' => $despesaAnt > 0 ? round((($despesa - $despesaAnt) / $despesaAnt) * 100, 1) : 0,
                'saldo'   => $saldoAnt != 0 ? round((($saldo - $saldoAnt) / abs($saldoAnt)) * 100, 1) : 0,
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // Cartões de crédito
    // Vencimento: due_day > closing_day → mesmo mês do fechamento
    //             due_day <= closing_day → mês seguinte ao fechamento
    // -------------------------------------------------------------------------
    private function getCartoes($user, array $networkIds): array
    {
        $now = now();

        return CreditCard::whereHas('users', fn($q) => $q->where('users.id', $user->id))
            ->get()
            ->map(function ($card) use ($now, $networkIds) {
                $start = $now->copy()->startOfMonth()->toDateString();
                $end   = $now->copy()->endOfMonth()->toDateString();

                $totalFatura = (float) Transaction::where('credit_card_id', $card->id)
                    ->whereBetween('transaction_date', [$start, $end])
                    ->whereHas('users', fn($q) => $q->whereIn('users.id', $networkIds))
                    ->sum('amount');

                $percentualUso = $card->limit > 0
                    ? round(($totalFatura / $card->limit) * 100, 2)
                    : 0;

                // Próximas 3 faturas
                $proximas = [];
                for ($i = 1; $i <= 3; $i++) {
                    $mes      = $now->copy()->addMonths($i);
                    $mesStart = $mes->copy()->startOfMonth()->toDateString();
                    $mesEnd   = $mes->copy()->endOfMonth()->toDateString();

                    $totalEstimado = (float) Transaction::where('credit_card_id', $card->id)
                        ->whereBetween('transaction_date', [$mesStart, $mesEnd])
                        ->whereHas('users', fn($q) => $q->whereIn('users.id', $networkIds))
                        ->sum('amount');

                    if ($totalEstimado > 0) {
                        $fechFutura = $mes->copy()->setDay(min((int) $card->closing_day, $mes->daysInMonth));
                        $vencFutura = $this->calcVencimento($fechFutura, $card);

                        $proximas[] = [
                            'mes'                      => $mes->format('Y-m'),
                            'status'                   => 'open',
                            'data_fechamento_estimada' => $fechFutura->format('Y-m-d'),
                            'data_vencimento_estimada' => $vencFutura->format('Y-m-d'),
                            'total_estimado'           => round($totalEstimado, 2),
                            'transacoes_confirmadas'   => Transaction::where('credit_card_id', $card->id)
                                ->whereBetween('transaction_date', [$mesStart, $mesEnd])
                                ->whereHas('users', fn($q) => $q->whereIn('users.id', $networkIds))
                                ->count(),
                        ];
                    }
                }

                // Fatura atual: próximo fechamento a partir de hoje
                $fechamento = $now->copy()->setDay(min((int) $card->closing_day, $now->daysInMonth));
                if ($fechamento->isPast()) {
                    $fechamento->addMonthNoOverflow();
                    $fechamento->day = min((int) $card->closing_day, $fechamento->daysInMonth);
                }
                $vencimento = $this->calcVencimento($fechamento, $card);

                return [
                    'id'               => $card->id,
                    'nome'             => $card->name,
                    'apelido'          => $card->alias ?? null,
                    'limite_total'     => (float) $card->limit,
                    'saldo_disponivel' => round((float) $card->limit - $totalFatura, 2),
                    'percentual_uso'   => $percentualUso,
                    'dia_fechamento'   => (int) $card->closing_day,
                    'dia_vencimento'   => (int) $card->due_day,
                    'fatura_aberta'    => [
                        'mes'              => $now->format('Y-m'),
                        'status'           => 'open',
                        'total'            => round($totalFatura, 2),
                        'data_fechamento'  => $fechamento->format('Y-m-d'),
                        'data_vencimento'  => $vencimento->format('Y-m-d'),
                        'transacoes_count' => Transaction::where('credit_card_id', $card->id)
                            ->whereBetween('transaction_date', [$start, $end])
                            ->whereHas('users', fn($q) => $q->whereIn('users.id', $networkIds))
                            ->count(),
                    ],
                    'faturas_proximas' => $proximas,
                ];
            })
            ->toArray();
    }

    // Se due_day > closing_day: vencimento no MESMO mês do fechamento
    // Se due_day <= closing_day: vencimento no mês SEGUINTE
    private function calcVencimento(Carbon $fechamento, $card): Carbon
    {
        if ((int) $card->due_day > (int) $card->closing_day) {
            $venc      = $fechamento->copy();
            $venc->day = min((int) $card->due_day, $venc->daysInMonth);
        } else {
            $venc      = $fechamento->copy()->addMonthNoOverflow();
            $venc->day = min((int) $card->due_day, $venc->daysInMonth);
        }
        return $venc;
    }

    // -------------------------------------------------------------------------
    // Contas fixas (recorrentes)
    // -------------------------------------------------------------------------
    private function getContasFixas($user, array $networkIds): array
    {
        $hoje = now();

        return RecurringTransaction::whereHas('users', fn($q) => $q->where('users.id', $user->id))
            ->where('is_active', true)
            ->with(['category', 'paymentMethod'])
            ->get()
            ->map(function ($conta) use ($hoje, $networkIds) {
                $diaVenc   = (int) ($conta->day_of_month ?? 1);
                $proxOcorr = Carbon::create(
                    $hoje->year,
                    $hoje->month,
                    min($diaVenc, $hoje->daysInMonth)
                );

                if ($proxOcorr->isPast()) {
                    $proxOcorr->addMonthNoOverflow();
                    $proxOcorr->day = min($diaVenc, $proxOcorr->daysInMonth);
                }

                $historico = Transaction::where('recurring_transaction_id', $conta->id)
                    ->whereHas('users', fn($q) => $q->whereIn('users.id', $networkIds))
                    ->orderBy('due_date', 'desc')
                    ->limit(6)
                    ->get()
                    ->map(fn($tx) => [
                        'mes'             => $tx->due_date->format('Y-m'),
                        'data_lancamento' => $tx->due_date->format('Y-m-d'),
                        'valor'           => round((float) $tx->amount, 2),
                    ])
                    ->reverse()
                    ->values()
                    ->toArray();

                $totalUlt6 = (float) Transaction::where('recurring_transaction_id', $conta->id)
                    ->whereHas('users', fn($q) => $q->whereIn('users.id', $networkIds))
                    ->whereDate('due_date', '>=', $hoje->copy()->subMonths(6)->startOfMonth())
                    ->sum('amount');

                return [
                    'id'                          => $conta->id,
                    'descricao'                   => $conta->description,
                    'valor_mensal'                => round((float) $conta->amount, 2),
                    'frequencia'                  => $conta->frequency === 'monthly' ? 'mensal' : 'anual',
                    'dia_vencimento'              => $diaVenc,
                    'categoria'                   => $conta->category->name ?? 'Sem categoria',
                    'forma_pagamento'             => $conta->paymentMethod->name ?? null,
                    'cartao_id'                   => $conta->credit_card_id,
                    'status'                      => $conta->is_active ? 'ativo' : 'pausado',
                    'proxima_ocorrencia'          => $proxOcorr->format('Y-m-d'),
                    'proxima_ocorrencia_dias'     => (int) $hoje->diffInDays($proxOcorr),
                    'historico_ultimos_6_meses'   => $historico,
                    'total_gasto_ultimos_6_meses' => round($totalUlt6, 2),
                ];
            })
            ->toArray();
    }

    // -------------------------------------------------------------------------
    // Compras parceladas — apenas installment_number=1 (compras 2026+)
    // -------------------------------------------------------------------------
    private function getComprasParceladas(array $networkIds): array
    {
        $hoje = now()->startOfDay();

        return Transaction::whereHas('users', fn($q) => $q->whereIn('users.id', $networkIds))
            ->whereNotNull('installment_total')
            ->where('installment_total', '>=', 2)
            ->where('installment_number', 1)
            ->with(['installments' => fn($q) => $q->orderBy('installment_number'), 'category', 'creditCard'])
            ->get()
            ->map(fn($tx) => $this->mapTransacaoParcelada($tx, $hoje))
            ->toArray();
    }

    // -------------------------------------------------------------------------
    // Todas as transações parceladas (incluindo installment_number > 1)
    // Necessário para que os alertas possam referenciar qualquer transaction_id
    // -------------------------------------------------------------------------
    private function todasTransacoesParceladas(array $networkIds): array
    {
        $hoje = now()->startOfDay();

        return Transaction::whereHas('users', fn($q) => $q->whereIn('users.id', $networkIds))
            ->whereNotNull('installment_total')
            ->where('installment_total', '>=', 2)
            ->with(['installments' => fn($q) => $q->orderBy('installment_number'), 'category', 'creditCard'])
            ->orderBy('description')
            ->orderBy('installment_number')
            ->get()
            ->map(fn($tx) => $this->mapTransacaoParcelada($tx, $hoje))
            ->toArray();
    }

    // -------------------------------------------------------------------------
    // Helper compartilhado: mapeia uma Transaction parcelada para o formato JSON
    // Arredondamento: última parcela absorve a diferença (sum = total_amount)
    // -------------------------------------------------------------------------
    private function mapTransacaoParcelada($tx, Carbon $hoje): array
    {
        $installments  = $tx->installments; // Collection já ordenada por installment_number
        $numInst       = $installments->count();

        // Última parcela = total - soma das demais (garante que a soma bate)
        $somaAnteriores = $numInst > 1
            ? (float) $installments->slice(0, $numInst - 1)->sum('amount')
            : 0;
        $valorUltima = $numInst > 0
            ? round((float) $tx->total_amount - $somaAnteriores, 2)
            : 0;

        $parcelas = $installments
            ->map(function ($inst, $index) use ($hoje, $numInst, $valorUltima) {
                $venc           = $inst->due_date->copy()->startOfDay();
                $diasParaVencer = (int) $hoje->diffInDays($venc, false);
                $valor          = $index === $numInst - 1
                    ? $valorUltima
                    : round((float) $inst->amount, 2);

                return [
                    'numero'           => (int) $inst->installment_number,
                    'data_vencimento'  => $inst->due_date->format('Y-m-d'),
                    'valor'            => $valor,
                    'status'           => $venc->isPast() ? 'paga' : 'pendente',
                    'dias_para_vencer' => $diasParaVencer >= 0 ? $diasParaVencer : null,
                ];
            })
            ->toArray();

        $parcelasPagas    = count(array_filter($parcelas, fn($p) => $p['status'] === 'paga'));
        $parcelasRestantes = (int) $tx->installment_total - $parcelasPagas;
        $valorTotalPago   = round(array_sum(
            array_column(array_filter($parcelas, fn($p) => $p['status'] === 'paga'), 'valor')
        ), 2);
        $valorNominal     = $tx->installment_total > 0
            ? round((float) $tx->total_amount / $tx->installment_total, 2)
            : 0;

        return [
            'id'              => $tx->id,
            'descricao'       => $tx->description,
            'valor_total'     => round((float) $tx->total_amount, 2),
            'numero_parcelas' => (int) $tx->installment_total,
            'valor_parcela'   => $valorNominal,
            'data_compra'     => $tx->transaction_date->format('Y-m-d'),
            'categoria'       => $tx->category->name ?? 'Sem categoria',
            'cartao_id'       => $tx->credit_card_id,
            'cartao_nome'     => $tx->creditCard->name ?? null,
            'status'          => $parcelasRestantes === 0 ? 'quitada' : 'em_andamento',
            'parcelas'        => $parcelas,
            'progresso'       => [
                'parcelas_pagas'       => $parcelasPagas,
                'parcelas_restantes'   => $parcelasRestantes,
                'percentual_pago'      => (int) $tx->installment_total > 0
                    ? round(($parcelasPagas / $tx->installment_total) * 100, 2)
                    : 0,
                'valor_total_pago'     => $valorTotalPago,
                'valor_total_restante' => round((float) $tx->total_amount - $valorTotalPago, 2),
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // Despesas por categoria (mês atual)
    // -------------------------------------------------------------------------
    private function getDespesasPorCategoria($user, array $networkIds): array
    {
        $start     = now()->startOfMonth()->toDateString();
        $end       = now()->endOfMonth()->toDateString();
        $prevStart = now()->subMonthNoOverflow()->startOfMonth()->toDateString();
        $prevEnd   = now()->subMonthNoOverflow()->endOfMonth()->toDateString();

        $despesas = Transaction::with('category')
            ->whereBetween('due_date', [$start, $end])
            ->whereHas('users', fn($q) => $q->whereIn('users.id', $networkIds))
            ->whereHas('type', fn($q) => $q->where('slug', 'dc'))
            ->get();

        $totalDespesa = (float) $despesas->sum('amount') ?: 1;

        return $despesas->groupBy('category_id')
            ->map(function ($grupo, $categoryId) use ($user, $networkIds, $totalDespesa, $prevStart, $prevEnd) {
                $category = $grupo->first()->category;
                $total    = (float) $grupo->sum('amount');

                $orcamento = CategoryBudget::where('user_id', $user->id)
                    ->where('category_id', $categoryId)
                    ->first();

                $orcamentoData = null;
                if ($orcamento) {
                    $pct           = round(($total / (float) $orcamento->amount) * 100, 2);
                    $status        = $pct >= 100 ? 'excedido' : ($pct >= 80 ? 'atencao' : 'ok');
                    $orcamentoData = [
                        'limite'     => round((float) $orcamento->amount, 2),
                        'gasto'      => round($total, 2),
                        'percentual' => $pct,
                        'restante'   => round((float) $orcamento->amount - $total, 2),
                        'status'     => $status,
                    ];
                }

                $despesaAnt = (float) Transaction::whereBetween('due_date', [$prevStart, $prevEnd])
                    ->where('category_id', $categoryId)
                    ->whereHas('users', fn($q) => $q->whereIn('users.id', $networkIds))
                    ->whereHas('type', fn($q) => $q->where('slug', 'dc'))
                    ->sum('amount') ?: 1;

                return [
                    'categoria'                => $category->name ?? 'Sem categoria',
                    'total'                    => round($total, 2),
                    'percentual_do_total'      => round(($total / $totalDespesa) * 100, 2),
                    'orcamento'                => $orcamentoData,
                    'transacoes_count'         => $grupo->count(),
                    'ticket_medio'             => round($total / $grupo->count(), 2),
                    'variacao_vs_mes_anterior' => round((($total - $despesaAnt) / $despesaAnt) * 100, 1),
                ];
            })
            ->values()
            ->toArray();
    }

    // -------------------------------------------------------------------------
    // Resumo dos últimos 6 meses
    // -------------------------------------------------------------------------
    private function getResumo6Meses(array $networkIds, Carbon $startDate): array
    {
        $meses = [];
        $data  = $startDate->copy();

        for ($i = 0; $i < 6; $i++) {
            $start = $data->copy()->startOfMonth()->toDateString();
            $end   = $data->copy()->endOfMonth()->toDateString();

            $receita = (float) Transaction::whereBetween('due_date', [$start, $end])
                ->whereHas('users', fn($q) => $q->whereIn('users.id', $networkIds))
                ->whereHas('type', fn($q) => $q->where('slug', 'rc'))
                ->sum('amount');

            $despesa = (float) Transaction::whereBetween('due_date', [$start, $end])
                ->whereHas('users', fn($q) => $q->whereIn('users.id', $networkIds))
                ->whereHas('type', fn($q) => $q->where('slug', 'dc'))
                ->sum('amount');

            $meses[] = [
                'mes'     => $data->format('Y-m'),
                'receita' => round($receita, 2),
                'despesa' => round($despesa, 2),
                'saldo'   => round($receita - $despesa, 2),
            ];

            $data->addMonthNoOverflow();
        }

        return $meses;
    }
}
