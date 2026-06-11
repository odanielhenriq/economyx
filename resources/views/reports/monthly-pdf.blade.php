<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Relatório mensal — {{ $report['header']['user_name'] }} — {{ $report['header']['month_label'] }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #1e293b;
            line-height: 1.45;
            margin: 0;
            padding: 0;
        }
        .page { padding: 28px 32px; }
        .cover {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 10px;
            padding: 22px 24px;
            margin-bottom: 22px;
        }
        .cover-top { margin-bottom: 14px; }
        .brand-badge {
            display: inline-block;
            background: #16a34a;
            color: #fff;
            font-weight: bold;
            padding: 5px 9px;
            border-radius: 8px;
            font-size: 11px;
            margin-right: 6px;
        }
        .brand-name { font-size: 13px; font-weight: bold; color: #166534; }
        .cover-title {
            font-size: 22px;
            font-weight: bold;
            color: #0f172a;
            margin: 0 0 6px;
        }
        .cover-user {
            font-size: 16px;
            color: #15803d;
            font-weight: bold;
            margin: 0 0 8px;
        }
        .cover-meta { color: #475569; margin: 0 0 2px; }
        .cover-meta small { color: #64748b; }
        h2 {
            font-size: 13px;
            color: #0f172a;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 4px;
            margin: 20px 0 10px;
        }
        .summary-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-left: 4px solid #16a34a;
            padding: 12px 14px;
            margin-bottom: 8px;
        }
        .summary-box p { margin: 0 0 8px; color: #334155; }
        .summary-box p:last-child { margin-bottom: 0; }
        .chips { margin: 8px 0 0; }
        .chip {
            display: inline-block;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 999px;
            padding: 3px 10px;
            font-size: 10px;
            color: #475569;
            margin-right: 6px;
            margin-bottom: 4px;
        }
        .chip.up { color: #15803d; border-color: #bbf7d0; }
        .chip.down { color: #dc2626; border-color: #fecaca; }
        .grid-2 { width: 100%; border-collapse: collapse; }
        .grid-2 td {
            width: 50%;
            vertical-align: top;
            padding: 6px 8px 6px 0;
        }
        .metric-label { color: #64748b; font-size: 10px; text-transform: uppercase; letter-spacing: 0.04em; }
        .metric-value { font-size: 14px; font-weight: bold; margin-top: 2px; }
        .positive { color: #15803d; }
        .negative { color: #dc2626; }
        .neutral { color: #0f172a; }
        table.data {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }
        table.data th,
        table.data td {
            border: 1px solid #e2e8f0;
            padding: 6px 8px;
            text-align: left;
        }
        table.data th {
            background: #f8fafc;
            font-size: 10px;
            text-transform: uppercase;
            color: #64748b;
        }
        table.data td.num { text-align: right; white-space: nowrap; }
        .alert {
            border: 1px solid #e2e8f0;
            border-left: 3px solid #16a34a;
            padding: 8px 10px;
            margin-bottom: 6px;
            background: #f8fafc;
        }
        .alert.warning { border-left-color: #d97706; }
        .alert.danger { border-left-color: #dc2626; }
        .alert-title { font-weight: bold; color: #0f172a; }
        .alert-message { color: #475569; margin-top: 2px; }
        .empty { color: #94a3b8; font-style: italic; padding: 4px 0; }
        .note { color: #94a3b8; font-size: 10px; margin-top: 8px; }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: bold;
        }
        .badge.ok { background: #dcfce7; color: #166534; }
        .badge.warn { background: #fef3c7; color: #92400e; }
        .warn { color: #b45309; }
        .footer {
            margin-top: 24px;
            padding-top: 10px;
            border-top: 1px solid #e2e8f0;
            color: #94a3b8;
            font-size: 9px;
        }
        .section-note { color: #64748b; font-size: 10px; margin: -6px 0 10px; }
    </style>
</head>
<body>
<div class="page">
    <div class="cover">
        <div class="cover-top">
            <span class="brand-badge">E$</span>
            <span class="brand-name">Economyx</span>
        </div>
        <p class="cover-title">Relatório mensal</p>
        <p class="cover-user">{{ $report['header']['user_name'] }}</p>
        <p class="cover-meta"><strong>{{ $report['header']['month_label'] }}</strong></p>
        <p class="cover-meta"><small>Período: {{ $report['header']['period_range'] }}</small></p>
        @if ($report['header']['has_network'])
            <p class="cover-meta"><small>Rede financeira: {{ $report['header']['network_label'] }}</small></p>
        @endif
        <p class="cover-meta"><small>Gerado em {{ $report['header']['generated_date'] }}</small></p>
    </div>

    <h2>Visão geral do mês</h2>
    <div class="summary-box">
        @foreach ($report['executive_summary'] as $paragraph)
            <p>{{ $paragraph }}</p>
        @endforeach
        @if ($report['comparison']['income_variation'] !== null || $report['comparison']['expense_variation'] !== null)
            <div class="chips">
                @if ($report['comparison']['income_variation'] !== null)
                    <span class="chip {{ $report['comparison']['income_variation'] >= 0 ? 'up' : 'down' }}">
                        Receitas {{ $report['comparison']['income_variation'] >= 0 ? '↑' : '↓' }}
                        {{ number_format(abs($report['comparison']['income_variation']), 1, ',', '.') }}%
                        vs {{ $report['comparison']['previous_month_label'] }}
                    </span>
                @endif
                @if ($report['comparison']['expense_variation'] !== null)
                    <span class="chip {{ $report['comparison']['expense_variation'] <= 0 ? 'up' : 'down' }}">
                        Despesas {{ $report['comparison']['expense_variation'] > 0 ? '↑' : '↓' }}
                        {{ number_format(abs($report['comparison']['expense_variation']), 1, ',', '.') }}%
                        vs {{ $report['comparison']['previous_month_label'] }}
                    </span>
                @endif
            </div>
        @endif
    </div>

    <h2>Resumo financeiro</h2>
    <p class="section-note">Indicadores consolidados de {{ $report['header']['month_label_short'] }}.</p>
    <table class="grid-2">
        <tr>
            <td>
                <div class="metric-label">Receitas do mês</div>
                <div class="metric-value positive">R$ {{ number_format($report['summary']['income_total'], 2, ',', '.') }}</div>
            </td>
            <td>
                <div class="metric-label">Despesas do mês</div>
                <div class="metric-value negative">R$ {{ number_format($report['summary']['expense_total'], 2, ',', '.') }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="metric-label">Saldo do mês</div>
                <div class="metric-value {{ $report['summary']['balance_total'] < 0 ? 'negative' : 'positive' }}">
                    R$ {{ number_format($report['summary']['balance_total'], 2, ',', '.') }}
                </div>
            </td>
            <td>
                <div class="metric-label">A pagar no mês</div>
                <div class="metric-value neutral">R$ {{ number_format($report['summary']['payable_total'], 2, ',', '.') }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="metric-label">Saldo projetado</div>
                <div class="metric-value {{ $report['summary']['projected_is_negative'] ? 'negative' : 'positive' }}">
                    R$ {{ number_format($report['summary']['projected_balance'], 2, ',', '.') }}
                </div>
            </td>
            <td>
                @if ($report['savings_goal']['exists'] ?? false)
                    <div class="metric-label">Meta de economia</div>
                    <div class="metric-value neutral">R$ {{ number_format($report['savings_goal']['target_amount'], 2, ',', '.') }}</div>
                    <div style="margin-top:4px;">
                        <span class="badge {{ ($report['savings_goal']['status'] ?? '') === 'on_track' ? 'ok' : 'warn' }}">
                            {{ $report['savings_goal']['status_label'] ?? '' }}
                        </span>
                    </div>
                    <div class="note">{{ $report['savings_goal']['message'] ?? '' }}</div>
                @else
                    <div class="metric-label">Meta de economia</div>
                    <div class="empty">Nenhuma meta definida para este mês.</div>
                @endif
            </td>
        </tr>
    </table>

    <h2>Composição do saldo projetado</h2>
    <table class="data">
        <tbody>
            <tr>
                <td>Receitas do mês</td>
                <td class="num positive">R$ {{ number_format($report['projected_breakdown']['income'], 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Despesas já lançadas</td>
                <td class="num negative">− R$ {{ number_format($report['projected_breakdown']['expenses_recorded'], 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td>A pagar no mês</td>
                <td class="num negative">− R$ {{ number_format($report['projected_breakdown']['payable'], 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Contas fixas previstas</td>
                <td class="num negative">− R$ {{ number_format($report['projected_breakdown']['recurring_projection'], 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td><strong>Saldo projetado</strong></td>
                <td class="num"><strong>R$ {{ number_format($report['projected_breakdown']['total'], 2, ',', '.') }}</strong></td>
            </tr>
        </tbody>
    </table>

    @if (count($report['payables']['cards']) > 0 || count($report['payables']['loans']) > 0)
        <h2>A pagar em {{ $report['header']['month_label_short'] }}</h2>
        @if (count($report['payables']['cards']) > 0)
            <p class="section-note">Faturas de cartão — total R$ {{ number_format($report['payables']['cards_total'], 2, ',', '.') }}</p>
            <table class="data">
                <thead>
                    <tr>
                        <th>Cartão</th>
                        <th class="num">Valor</th>
                        <th>Vencimento</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($report['payables']['cards'] as $item)
                        <tr>
                            <td>{{ $item['card_name'] ?? $item['description'] ?? 'Cartão' }}</td>
                            <td class="num">R$ {{ number_format($item['amount'] ?? 0, 2, ',', '.') }}</td>
                            <td>{{ isset($item['due_date']) ? \Carbon\Carbon::parse($item['due_date'])->format('d/m/Y') : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
        @if (count($report['payables']['loans']) > 0)
            <p class="section-note" style="margin-top:12px;">Parcelas de empréstimos — total R$ {{ number_format($report['payables']['loans_total'], 2, ',', '.') }}</p>
            <table class="data">
                <thead>
                    <tr>
                        <th>Descrição</th>
                        <th class="num">Valor</th>
                        <th>Vencimento</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($report['payables']['loans'] as $item)
                        <tr>
                            <td>{{ $item['description'] ?? 'Parcela' }}</td>
                            <td class="num">R$ {{ number_format($item['amount'] ?? 0, 2, ',', '.') }}</td>
                            <td>{{ isset($item['due_date']) ? \Carbon\Carbon::parse($item['due_date'])->format('d/m/Y') : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endif

    <h2>Alertas do mês</h2>
    @if (count($report['alerts']) === 0)
        <p class="empty">Nenhum alerta importante para este mês.</p>
    @else
        @foreach ($report['alerts'] as $alert)
            <div class="alert {{ $alert['severity'] ?? 'info' }}">
                <div class="alert-title">{{ $alert['title'] }}</div>
                <div class="alert-message">{{ $alert['message'] }}</div>
            </div>
        @endforeach
    @endif

    <h2>Gastos por categoria</h2>
    @if (count($report['categories']) === 0)
        <p class="empty">Nenhuma despesa categorizada neste mês.</p>
    @else
        @if ($report['top_category'])
            <p class="section-note">
                Maior gasto: <strong>{{ $report['top_category']['category'] }}</strong>
                (R$ {{ number_format($report['top_category']['total'], 2, ',', '.') }},
                {{ number_format($report['top_category']['share_percent'], 1, ',', '.') }}% do total)
            </p>
        @endif
        <table class="data">
            <thead>
                <tr>
                    <th>Categoria</th>
                    <th class="num">Valor</th>
                    <th class="num">% do total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($report['categories'] as $category)
                    <tr>
                        <td>{{ $category['category'] }}</td>
                        <td class="num">R$ {{ number_format($category['total'], 2, ',', '.') }}</td>
                        <td class="num">{{ number_format($category['share_percent'], 1, ',', '.') }}%</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2>Próximos compromissos</h2>
    @if (count($report['future_commitments']) === 0)
        <p class="empty">Nenhum compromisso previsto nos próximos meses.</p>
    @else
        <table class="data">
            <thead>
                <tr>
                    <th>Mês</th>
                    <th class="num">Parcelas</th>
                    <th class="num">Contas fixas</th>
                    <th class="num">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($report['future_commitments'] as $commitment)
                    <tr>
                        <td>{{ $commitment['label'] ?? '' }}</td>
                        <td class="num">R$ {{ number_format($commitment['installments_total'] ?? 0, 2, ',', '.') }}</td>
                        <td class="num">R$ {{ number_format($commitment['recurring_total'] ?? 0, 2, ',', '.') }}</td>
                        <td class="num">R$ {{ number_format($commitment['total'] ?? 0, 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        @if ($report['future_commitments_note'])
            <p class="note">{{ $report['future_commitments_note'] }}</p>
        @endif
    @endif

    <h2>Compras parceladas ativas</h2>
    @if (count($report['installment_purchases']) === 0)
        <p class="empty">Nenhuma compra parcelada ativa no momento.</p>
    @else
        <table class="data">
            <thead>
                <tr>
                    <th>Compra</th>
                    <th class="num">Parcela</th>
                    <th class="num">Restante</th>
                    <th>Próxima parcela</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($report['installment_purchases'] as $purchase)
                    <tr>
                        <td>{{ $purchase['description'] }}</td>
                        <td class="num">{{ $purchase['current_installment'] }}/{{ $purchase['total_installments'] }}</td>
                        <td class="num">R$ {{ number_format($purchase['remaining_amount'], 2, ',', '.') }}</td>
                        <td>
                            @if ($purchase['next_due_date'])
                                {{ \Carbon\Carbon::parse($purchase['next_due_date'])->format('d/m/Y') }}
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        @if ($report['installment_purchases_overflow'] > 0)
            <p class="note">+ {{ $report['installment_purchases_overflow'] }} compras parceladas ativas.</p>
        @endif
    @endif

    @if ($report['shared_expenses'])
        <h2>Gastos compartilhados em {{ $report['header']['month_label_short'] }}</h2>
        <table class="grid-2">
            <tr>
                <td>
                    <div class="metric-label">Total compartilhado</div>
                    <div class="metric-value neutral">R$ {{ number_format($report['shared_expenses']['total_shared'], 2, ',', '.') }}</div>
                </td>
                <td>
                    <div class="metric-label">Pendente de acerto</div>
                    <div class="metric-value warn">R$ {{ number_format($report['shared_expenses']['pending_settlement'], 2, ',', '.') }}</div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="metric-label">Já acertado</div>
                    <div class="metric-value positive">R$ {{ number_format($report['shared_expenses']['settled_total'], 2, ',', '.') }}</div>
                </td>
                <td></td>
            </tr>
        </table>
        @if (count($report['shared_expenses']['suggestions']) > 0)
            <p class="note"><strong>Sugestões de acerto:</strong></p>
            @foreach ($report['shared_expenses']['suggestions'] as $suggestion)
                <div class="alert">
                    <div class="alert-message">{{ $suggestion }}</div>
                </div>
            @endforeach
        @endif
    @endif

    <div class="footer">
        Relatório personalizado de {{ $report['header']['user_name'] }} — {{ $report['header']['month_label'] }}.
        Gerado pelo Economyx em {{ $report['header']['generated_at'] }}.
        Valores projetados são estimativas e podem mudar conforme novos lançamentos.
    </div>
</div>
</body>
</html>
