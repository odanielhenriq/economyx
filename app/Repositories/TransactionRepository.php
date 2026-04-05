<?php

namespace App\Repositories;

use App\Models\Transaction;
use App\Models\CreditCard;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Repository responsável por todas as operações de banco de dados relacionadas a transações.
 * 
 * Este repository abstrai o acesso ao banco de dados, seguindo o padrão Repository.
 * Todas as queries e operações CRUD de transações passam por aqui.
 * 
 * Responsabilidades:
 * - CRUD de transações
 * - Filtros e paginação
 * - Cálculo automático de due_date
 * - Relacionamento com usuários
 * 
 * @see App\Repositories\TransactionRepositoryInterface
 */
class TransactionRepository implements TransactionRepositoryInterface
{
    /**
     * Retorna todas as transações (sem paginação).
     */
    public function getAllTransactions(): Collection
    {
        return Transaction::with([
            'category',
            'type',
            'paymentMethod',
            'users'
        ])->get();
    }

    /**
     * Lista paginada com filtros.
     */
    public function getPaginatedTransactions(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {

        $query = Transaction::with([
            'category',
            'type',
            'paymentMethod',
            'creditCard',
            'users'
        ])->orderByDesc('due_date');

        // Filtro por usuário (relacionamento N:N)
        if (!empty($filters['user_id'])) {
            $query->whereHas('users', function ($q) use ($filters) {
                $q->where('users.id', $filters['user_id']);
            });
        }

        // Filtro por mês no formato "YYYY-MM"
        if (!empty($filters['month'])) {
            [$year, $month] = explode('-', $filters['month']);

            $query
                ->whereYear('due_date', $year)
                ->whereMonth('due_date', $month);
        }

        if (!empty($filters['year'])) {
            $query->whereYear('due_date', $filters['year']);
        }

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['type_id'])) {
            $query->where('type_id', $filters['type_id']);
        }

        if (!empty($filters['payment_method_id'])) {
            $query->where('payment_method_id', $filters['payment_method_id']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Cria transação e relaciona com usuários.
     */
    public function createTransaction(array $data, array $userIds): Transaction
    {
        // Calcula due_date se não foi fornecido
        if (empty($data['due_date']) && !empty($data['transaction_date'])) {
            $data['due_date'] = $this->calculateDueDate($data);
        }

        // Se o método de pagamento NÃO é cartão de crédito (ID 1), remove credit_card_id
        // Mas só remove se realmente não for cartão (não remove se vier vazio mas for cartão)
        if (!empty($data['payment_method_id']) && $data['payment_method_id'] != 1) {
            $data['credit_card_id'] = null;
        }
        
        // Se é cartão de crédito (ID 1) mas credit_card_id está vazio, garante que seja null
        // (não deixa string vazia)
        if (!empty($data['payment_method_id']) && $data['payment_method_id'] == 1) {
            if (empty($data['credit_card_id']) || $data['credit_card_id'] === '') {
                $data['credit_card_id'] = null;
            }
        }

        $transaction = Transaction::create($data);

        // Relaciona usuários
        $transaction->users()->sync($userIds);

        return $transaction->load(['category', 'type', 'paymentMethod', 'users']);
    }

    /**
     * Calcula o due_date automaticamente baseado nas regras do sistema.
     * 
     * Regras:
     * 1. Cartão à vista: mês seguinte + dia do cartão
     * 2. Cartão parcelado: será atualizado quando parcelas forem geradas
     * 3. Empréstimo: usa first_due_date ou transaction_date + 1 mês
     * 4. Outros: usa transaction_date
     * 
     * @param array $data Dados da transação
     * @return string|null Data de vencimento no formato Y-m-d
     */
    private function calculateDueDate(array $data): ?string
    {
        $transactionDate = \Carbon\Carbon::parse($data['transaction_date']);

        // Se tem cartão de crédito
        if (!empty($data['credit_card_id'])) {
            $card = \App\Models\CreditCard::find($data['credit_card_id']);
            
            if ($card) {
                // Se é parcelada, o due_date será calculado quando as parcelas forem geradas
                // Por enquanto, usa o mês seguinte ao da compra
                if (!empty($data['installment_total']) && $data['installment_total'] > 1) {
                    $dueMonth = $transactionDate->copy()->addMonthNoOverflow();
                    return \Carbon\Carbon::create($dueMonth->year, $dueMonth->month, min($card->due_day, $dueMonth->daysInMonth))->format('Y-m-d');
                }
                
                // Compra à vista: vence no mês seguinte ao da compra
                $dueMonth = $transactionDate->copy()->addMonthNoOverflow();
                return \Carbon\Carbon::create($dueMonth->year, $dueMonth->month, min($card->due_day, $dueMonth->daysInMonth))->format('Y-m-d');
            }
        }
        
        // Se é empréstimo parcelado
        if (!empty($data['installment_total']) && $data['installment_total'] > 1) {
            if (!empty($data['first_due_date'])) {
                return $data['first_due_date'];
            }
            // Usa transaction_date + 1 mês
            return $transactionDate->copy()->addMonthNoOverflow()->format('Y-m-d');
        }
        
        // Caso padrão: usa transaction_date
        return $transactionDate->format('Y-m-d');
    }

    public function findTransactionById(int $id): ?Transaction
    {
        return Transaction::with([
            'category',
            'type',
            'paymentMethod',
            'users'
        ])->find($id);
    }

    public function updateTransaction(int $id, array $data, ?array $userIds = null): Transaction
    {
        $transaction = Transaction::findOrFail($id);

        // Calcula due_date se não foi fornecido
        if (empty($data['due_date']) && !empty($data['transaction_date'])) {
            $data['due_date'] = $this->calculateDueDate($data);
        }

        if (($data['payment_method_slug'] ?? '') !== 'cc') {
            $data['credit_card_id'] = null;
        }

        // Garante total_amount se vier vazio
        if (empty($data['total_amount'])) {
            $data['total_amount'] = $data['amount'] ?? null;
        }

        $transaction->update($data);

        if ($userIds !== null) {
            $transaction->users()->sync($userIds);
        }

        return $transaction->load([
            'category',
            'type',
            'paymentMethod',
            'creditCard',
            'users',
        ]);
    }

    public function deleteTransaction(int $id): bool
    {
        $transaction = Transaction::find($id);

        if (! $transaction) {
            return false;
        }

        // Soft delete
        $transaction->delete();

        return true;
    }

    /**
     * Monta um "extrato" de cartão com base em transaction_date
     * (VERSÃO ANTIGA, sem usar credit_card_statements + installments).
     *
     * Hoje você já está migrando isso para a estrutura nova no CardStatementController.
     */
    public function getCardBill(int $cardId, int $year, int $month): array
    {
        // 1) Carrega o cartão com o dono
        $card = CreditCard::with('owner')->findOrFail($cardId);

        // 2) Calcula o período de faturamento usando método do model
        [$periodStart, $periodEnd] = $card->getBillingPeriodFor($year, $month);

        // 3) Busca transações do cartão dentro do período
        $transactions = Transaction::with([
            'category',
            'type',
            'paymentMethod',
            'creditCard',
            'users',
        ])
            ->where('credit_card_id', $card->id)
            ->whereBetween('transaction_date', [
                $periodStart->toDateString(),
                $periodEnd->toDateString(),
            ])
            ->orderBy('transaction_date')
            ->get();

        // 4) Calcula resumo (separando receita/despesa por type.slug)
        $income  = 0.0;
        $expense = 0.0;

        foreach ($transactions as $tx) {
            $slug = $tx->type?->slug; // ex.: 'rc' ou 'dc'
            $amount = (float) $tx->amount;

            $signed = $slug === 'rc'
                ? $amount      // receita
                : -1 * $amount; // despesa

            if ($signed > 0) {
                $income += $signed;
            } else {
                $expense += $signed;
            }
        }

        return [
            'card' => $card,
            'period' => [
                'start' => $periodStart,
                'end'   => $periodEnd,
            ],
            'summary' => [
                'income'  => round($income, 2),
                'expense' => round($expense, 2),
                'net'     => round($income + $expense, 2),
            ],
            'transactions' => $transactions,
        ];
    }
}
