<?php

use App\Models\Category;
use App\Models\CategoryBudget;
use App\Models\MonthlySavingsGoal;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Models\Type;
use App\Models\User;
use App\Services\MonthlyReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

function monthlyReportFixtures(): array
{
    $category = Category::create(['name' => 'Mercado MR', 'slug' => 'mercado-mr']);
    $expenseType = Type::create(['name' => 'Despesa MR', 'slug' => 'dc']);
    $incomeType = Type::create(['name' => 'Receita MR', 'slug' => 'rc']);
    $paymentMethod = PaymentMethod::create(['name' => 'Pix MR', 'slug' => 'pix-mr']);

    return compact('category', 'expenseType', 'incomeType', 'paymentMethod');
}

function seedMonthlyReportData(User $user, int $year, int $month): void
{
    ['category' => $category, 'expenseType' => $expenseType, 'incomeType' => $incomeType, 'paymentMethod' => $paymentMethod] = monthlyReportFixtures();
    $date = Carbon::create($year, $month, 10)->toDateString();

    $income = Transaction::create([
        'description' => 'Salario',
        'amount' => 3000.00,
        'total_amount' => 3000.00,
        'transaction_date' => $date,
        'due_date' => $date,
        'category_id' => $category->id,
        'type_id' => $incomeType->id,
        'payment_method_id' => $paymentMethod->id,
    ]);
    $income->users()->sync([$user->id]);

    $expense = Transaction::create([
        'description' => 'Mercado',
        'amount' => 400.00,
        'total_amount' => 400.00,
        'transaction_date' => $date,
        'due_date' => $date,
        'category_id' => $category->id,
        'type_id' => $expenseType->id,
        'payment_method_id' => $paymentMethod->id,
    ]);
    $expense->users()->sync([$user->id]);
}

it('rota de pdf exige autenticacao', function () {
    $this->get(route('reports.monthly.pdf', ['year' => 2026, 'month' => 6]))
        ->assertRedirect(route('login'));
});

it('usuario autenticado consegue gerar pdf do mes', function () {
    $user = User::factory()->create();
    seedMonthlyReportData($user, 2026, 6);

    $response = $this->actingAs($user)->get(route('reports.monthly.pdf', ['year' => 2026, 'month' => 6]));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});

it('pdf usa mes e ano enviados no nome do arquivo', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('reports.monthly.pdf', ['year' => 2026, 'month' => 6]));

    $response->assertOk();
    expect($response->headers->get('content-disposition'))->toContain('economyx-relatorio-2026-06.pdf');
});

it('service monta resumo financeiro do mes', function () {
    $user = User::factory()->create();
    seedMonthlyReportData($user, 2026, 6);

    $report = app(MonthlyReportService::class)->build(2026, 6, $user);

    expect($report['summary']['income_total'])->toBe(3000.0);
    expect($report['summary']['expense_total'])->toBe(400.0);
    expect($report['summary']['projected_balance'])->toBe(2600.0);
});

it('relatorio inclui meta de economia quando existir', function () {
    $user = User::factory()->create();
    seedMonthlyReportData($user, 2026, 6);

    MonthlySavingsGoal::create([
        'user_id' => $user->id,
        'year' => 2026,
        'month' => 6,
        'target_amount' => 500,
    ]);

    $report = app(MonthlyReportService::class)->build(2026, 6, $user);

    expect($report['savings_goal']['exists'])->toBeTrue();
    expect($report['savings_goal']['target_amount'])->toBe(500.0);
    expect($report['savings_goal']['status'])->toBe('on_track');
});

it('relatorio funciona sem meta de economia', function () {
    $user = User::factory()->create();
    seedMonthlyReportData($user, 2026, 6);

    $report = app(MonthlyReportService::class)->build(2026, 6, $user);

    expect($report['savings_goal']['exists'])->toBeFalse();
});

it('relatorio funciona sem alertas', function () {
    $user = User::factory()->create();

    $report = app(MonthlyReportService::class)->build(2026, 6, $user);

    expect($report['alerts'])->toBeArray();
});

it('relatorio inclui alertas quando existirem', function () {
    $user = User::factory()->create();
    ['category' => $category] = monthlyReportFixtures();

    CategoryBudget::create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'amount' => 100,
    ]);

    $date = Carbon::create(2026, 6, 10)->toDateString();
    ['expenseType' => $expenseType, 'paymentMethod' => $paymentMethod] = monthlyReportFixtures();
    $expense = Transaction::create([
        'description' => 'Mercado estourado',
        'amount' => 150.00,
        'total_amount' => 150.00,
        'transaction_date' => $date,
        'due_date' => $date,
        'category_id' => $category->id,
        'type_id' => $expenseType->id,
        'payment_method_id' => $paymentMethod->id,
    ]);
    $expense->users()->sync([$user->id]);

    $report = app(MonthlyReportService::class)->build(2026, 6, $user);

    expect(count($report['alerts']))->toBeGreaterThan(0);
});

it('nao expoe dados de outro usuario fora da rede no relatorio', function () {
    $userA = User::factory()->create(['name' => 'Daniel']);
    $userB = User::factory()->create(['name' => 'Carlos']);
    seedMonthlyReportData($userB, 2026, 6);

    $report = app(MonthlyReportService::class)->build(2026, 6, $userA);

    expect($report['summary']['income_total'])->toBe(0.0);
    expect($report['summary']['expense_total'])->toBe(0.0);
});

it('view do relatorio contem resumo financeiro', function () {
    $user = User::factory()->create(['name' => 'Ana Silva']);
    seedMonthlyReportData($user, 2026, 6);

    $report = app(MonthlyReportService::class)->build(2026, 6, $user);
    $html = view('reports.monthly-pdf', compact('report'))->render();

    expect($html)->toContain('Relatório mensal');
    expect($html)->toContain('Ana Silva');
    expect($html)->toContain('Visão geral do mês');
    expect($html)->toContain('Saldo projetado');
    expect($html)->toContain('Receitas do mês');
});

it('relatorio usa mes e ano selecionados no cabecalho', function () {
    $user = User::factory()->create(['name' => 'Bruno']);

    $report = app(MonthlyReportService::class)->build(2024, 3, $user);

    expect($report['header']['period_range'])->toBe('01/03/2024 — 31/03/2024');
    expect($report['header']['month_label_short'])->toBe('mar/2024');
    expect($report['executive_summary'][0])->toContain('Bruno');
});

it('relatorio inclui resumo executivo personalizado com dados do mes', function () {
    $user = User::factory()->create(['name' => 'Carla']);
    seedMonthlyReportData($user, 2026, 6);

    $report = app(MonthlyReportService::class)->build(2026, 6, $user);

    expect($report['executive_summary'])->not->toBeEmpty();
    expect($report['executive_summary'][0])->toContain('Carla');
    expect(strtolower($report['executive_summary'][0]))->toContain('junho');
    expect($report['projected_breakdown']['total'])->toBe(2600.0);
});

it('pdf gerado contem marcador pdf', function () {
    $user = User::factory()->create();
    seedMonthlyReportData($user, 2026, 6);

    $report = app(MonthlyReportService::class)->build(2026, 6, $user);
    $output = Pdf::loadView('reports.monthly-pdf', compact('report'))->output();

    expect(substr($output, 0, 4))->toBe('%PDF');
});

it('export json existente continua funcionando', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('export.json'))
        ->assertOk()
        ->assertJsonStructure(['usuario', 'periodo_analise']);
});

it('export csv existente continua funcionando', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('transactions.export', [
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
        ]))
        ->assertOk();
});

it('filename helper retorna nome correto', function () {
    expect(app(MonthlyReportService::class)->filename(2026, 6))
        ->toBe('economyx-relatorio-2026-06.pdf');
});
