<?php

use App\Models\Category;
use App\Models\CategoryBudget;
use App\Models\CreditCard;
use App\Models\CreditCardStatement;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Models\TransactionInstallment;
use App\Models\Type;
use App\Models\User;
use App\Services\FinancialAlertService;
use Carbon\Carbon;

function alertFixtures(User $user): array
{
    $category = Category::create(['name' => 'Mercado', 'slug' => 'merc-al']);
    $expenseType = Type::create(['name' => 'Despesa', 'slug' => 'dc']);
    $incomeType = Type::create(['name' => 'Receita', 'slug' => 'rc']);
    $paymentMethod = PaymentMethod::create(['name' => 'Pix', 'slug' => 'pix-al']);

    return compact('category', 'expenseType', 'incomeType', 'paymentMethod');
}

it('gera alerta de orcamento acima de 80 por cento', function () {
    Carbon::setTestNow(Carbon::create(2025, 8, 15));
    $user = User::factory()->create();
    ['category' => $category, 'expenseType' => $expenseType, 'paymentMethod' => $paymentMethod] = alertFixtures($user);

    CategoryBudget::create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'amount' => 1000,
    ]);

    $tx = Transaction::create([
        'description' => 'Compras',
        'amount' => 850,
        'total_amount' => 850,
        'transaction_date' => '2025-08-10',
        'due_date' => '2025-08-10',
        'category_id' => $category->id,
        'type_id' => $expenseType->id,
        'payment_method_id' => $paymentMethod->id,
    ]);
    $tx->users()->sync([$user->id]);

    $alerts = app(FinancialAlertService::class)->collect(2025, 8, $user);

    expect(collect($alerts)->contains(fn ($a) => $a['type'] === 'budget_warning'))->toBeTrue();
});

it('gera alerta quando orcamento atinge 100 por cento', function () {
    Carbon::setTestNow(Carbon::create(2025, 8, 15));
    $user = User::factory()->create();
    ['category' => $category, 'expenseType' => $expenseType, 'paymentMethod' => $paymentMethod] = alertFixtures($user);

    CategoryBudget::create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'amount' => 500,
    ]);

    $tx = Transaction::create([
        'description' => 'Compras',
        'amount' => 500,
        'total_amount' => 500,
        'transaction_date' => '2025-08-10',
        'due_date' => '2025-08-10',
        'category_id' => $category->id,
        'type_id' => $expenseType->id,
        'payment_method_id' => $paymentMethod->id,
    ]);
    $tx->users()->sync([$user->id]);

    $alerts = app(FinancialAlertService::class)->collect(2025, 8, $user);

    expect(collect($alerts)->contains(fn ($a) => $a['type'] === 'budget_reached'))->toBeTrue();
});

it('gera alerta quando orcamento e excedido', function () {
    Carbon::setTestNow(Carbon::create(2025, 8, 15));
    $user = User::factory()->create();
    ['category' => $category, 'expenseType' => $expenseType, 'paymentMethod' => $paymentMethod] = alertFixtures($user);

    CategoryBudget::create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'amount' => 400,
    ]);

    $tx = Transaction::create([
        'description' => 'Compras',
        'amount' => 520,
        'total_amount' => 520,
        'transaction_date' => '2025-08-10',
        'due_date' => '2025-08-10',
        'category_id' => $category->id,
        'type_id' => $expenseType->id,
        'payment_method_id' => $paymentMethod->id,
    ]);
    $tx->users()->sync([$user->id]);

    $alert = collect(app(FinancialAlertService::class)->collect(2025, 8, $user))
        ->firstWhere('type', 'budget_exceeded');

    expect($alert)->not->toBeNull()
        ->and($alert['message'])->toContain('R$ 120,00');
});

it('gera alerta de fatura vencida', function () {
    Carbon::setTestNow(Carbon::create(2025, 8, 20));
    $user = User::factory()->create();

    $card = CreditCard::create([
        'name' => 'Nubank',
        'closing_day' => 5,
        'due_day' => 10,
        'owner_user_id' => $user->id,
        'is_shared' => true,
    ]);
    $card->users()->sync([$user->id]);

    CreditCardStatement::create([
        'credit_card_id' => $card->id,
        'year' => 2025,
        'month' => 8,
        'period_start' => '2025-08-01',
        'period_end' => '2025-08-31',
        'closing_day' => 5,
        'due_day' => 10,
        'status' => 'open',
    ]);

    $alerts = app(FinancialAlertService::class)->collect(2025, 8, $user);

    expect(collect($alerts)->contains(fn ($a) => $a['type'] === 'invoice_overdue'))->toBeTrue();
});

it('gera alerta de fatura vencendo hoje', function () {
    Carbon::setTestNow(Carbon::create(2025, 8, 10));
    $user = User::factory()->create();

    $card = CreditCard::create([
        'name' => 'Santander',
        'closing_day' => 5,
        'due_day' => 10,
        'owner_user_id' => $user->id,
        'is_shared' => true,
    ]);
    $card->users()->sync([$user->id]);

    CreditCardStatement::create([
        'credit_card_id' => $card->id,
        'year' => 2025,
        'month' => 8,
        'period_start' => '2025-08-01',
        'period_end' => '2025-08-31',
        'closing_day' => 5,
        'due_day' => 10,
        'status' => 'open',
    ]);

    $alerts = app(FinancialAlertService::class)->collect(2025, 8, $user);

    expect(collect($alerts)->contains(fn ($a) => $a['type'] === 'invoice_due_today'))->toBeTrue();
});

it('gera alerta de fatura vencendo nos proximos 7 dias', function () {
    Carbon::setTestNow(Carbon::create(2025, 8, 12));
    $user = User::factory()->create();

    $card = CreditCard::create([
        'name' => 'Inter',
        'closing_day' => 5,
        'due_day' => 15,
        'owner_user_id' => $user->id,
        'is_shared' => true,
    ]);
    $card->users()->sync([$user->id]);

    CreditCardStatement::create([
        'credit_card_id' => $card->id,
        'year' => 2025,
        'month' => 8,
        'period_start' => '2025-08-01',
        'period_end' => '2025-08-31',
        'closing_day' => 5,
        'due_day' => 15,
        'status' => 'open',
    ]);

    $alerts = app(FinancialAlertService::class)->collect(2025, 8, $user);

    expect(collect($alerts)->contains(fn ($a) => $a['type'] === 'invoice_due_soon'))->toBeTrue();
});

it('gera alerta de cartao acima de 75 por cento do limite', function () {
    Carbon::setTestNow(Carbon::create(2025, 8, 15));
    $user = User::factory()->create();
    ['category' => $category, 'expenseType' => $expenseType, 'paymentMethod' => $paymentMethod] = alertFixtures($user);

    $card = CreditCard::create([
        'name' => 'Nubank',
        'closing_day' => 5,
        'due_day' => 10,
        'limit' => 1000,
        'owner_user_id' => $user->id,
        'is_shared' => true,
    ]);
    $card->users()->sync([$user->id]);

    $tx = Transaction::create([
        'description' => 'Compra',
        'amount' => 780,
        'total_amount' => 780,
        'transaction_date' => '2025-08-05',
        'due_date' => '2025-08-05',
        'category_id' => $category->id,
        'type_id' => $expenseType->id,
        'payment_method_id' => $paymentMethod->id,
        'credit_card_id' => $card->id,
    ]);
    $tx->users()->sync([$user->id]);

    $alerts = app(FinancialAlertService::class)->collect(2025, 8, $user);

    expect(collect($alerts)->contains(fn ($a) => $a['type'] === 'card_high_usage'))->toBeTrue();
});

it('nao gera alerta de cartao quando limite nao esta cadastrado', function () {
    Carbon::setTestNow(Carbon::create(2025, 8, 15));
    $user = User::factory()->create();
    ['category' => $category, 'expenseType' => $expenseType, 'paymentMethod' => $paymentMethod] = alertFixtures($user);

    $card = CreditCard::create([
        'name' => 'Sem limite',
        'closing_day' => 5,
        'due_day' => 10,
        'limit' => 0,
        'owner_user_id' => $user->id,
        'is_shared' => true,
    ]);
    $card->users()->sync([$user->id]);

    $tx = Transaction::create([
        'description' => 'Compra',
        'amount' => 900,
        'total_amount' => 900,
        'transaction_date' => '2025-08-05',
        'due_date' => '2025-08-05',
        'category_id' => $category->id,
        'type_id' => $expenseType->id,
        'payment_method_id' => $paymentMethod->id,
        'credit_card_id' => $card->id,
    ]);
    $tx->users()->sync([$user->id]);

    $alerts = app(FinancialAlertService::class)->collect(2025, 8, $user);

    expect(collect($alerts)->contains(fn ($a) => $a['type'] === 'card_high_usage'))->toBeFalse();
});

it('gera alerta de parcela terminando nos proximos 30 dias', function () {
    Carbon::setTestNow(Carbon::create(2025, 8, 10));
    $user = User::factory()->create();
    ['category' => $category, 'expenseType' => $expenseType, 'paymentMethod' => $paymentMethod] = alertFixtures($user);

    $tx = Transaction::create([
        'description' => 'Notebook',
        'amount' => 300,
        'total_amount' => 900,
        'transaction_date' => '2025-05-01',
        'due_date' => '2025-05-01',
        'installment_number' => 3,
        'installment_total' => 3,
        'category_id' => $category->id,
        'type_id' => $expenseType->id,
        'payment_method_id' => $paymentMethod->id,
    ]);
    $tx->users()->sync([$user->id]);

    TransactionInstallment::create([
        'transaction_id' => $tx->id,
        'installment_number' => 3,
        'installment_total' => 3,
        'amount' => 300,
        'year' => 2025,
        'month' => 8,
        'due_date' => '2025-08-25',
    ]);

    $alerts = app(FinancialAlertService::class)->collect(2025, 8, $user);

    expect(collect($alerts)->contains(fn ($a) => $a['type'] === 'installment_ending'))->toBeTrue();
});

it('gera alertas agrupados para contas vencidas e proximas', function () {
    Carbon::setTestNow(Carbon::create(2025, 8, 15));
    $user = User::factory()->create();
    ['category' => $category, 'expenseType' => $expenseType, 'paymentMethod' => $paymentMethod] = alertFixtures($user);

    foreach (['2025-08-10', '2025-08-15', '2025-08-20'] as $dueDate) {
        $tx = Transaction::create([
            'description' => "Conta {$dueDate}",
            'amount' => 100,
            'total_amount' => 100,
            'transaction_date' => $dueDate,
            'due_date' => $dueDate,
            'category_id' => $category->id,
            'type_id' => $expenseType->id,
            'payment_method_id' => $paymentMethod->id,
        ]);
        $tx->users()->sync([$user->id]);
    }

    $types = collect(app(FinancialAlertService::class)->collect(2025, 8, $user))->pluck('type');

    expect($types)->toContain('transactions_overdue')
        ->and($types)->toContain('transactions_due_today')
        ->and($types)->toContain('transactions_due_soon');
});

it('ordena alertas por prioridade', function () {
    Carbon::setTestNow(Carbon::create(2025, 8, 20));
    $user = User::factory()->create();
    ['category' => $category, 'expenseType' => $expenseType, 'paymentMethod' => $paymentMethod] = alertFixtures($user);

    CategoryBudget::create(['user_id' => $user->id, 'category_id' => $category->id, 'amount' => 100]);
    $tx = Transaction::create([
        'description' => 'Conta vencida',
        'amount' => 50,
        'total_amount' => 50,
        'transaction_date' => '2025-08-05',
        'due_date' => '2025-08-05',
        'category_id' => $category->id,
        'type_id' => $expenseType->id,
        'payment_method_id' => $paymentMethod->id,
    ]);
    $tx->users()->sync([$user->id]);

    $over = Transaction::create([
        'description' => 'Gasto extra',
        'amount' => 150,
        'total_amount' => 150,
        'transaction_date' => '2025-08-10',
        'due_date' => '2025-08-10',
        'category_id' => $category->id,
        'type_id' => $expenseType->id,
        'payment_method_id' => $paymentMethod->id,
    ]);
    $over->users()->sync([$user->id]);

    $card = CreditCard::create([
        'name' => 'Nubank',
        'closing_day' => 5,
        'due_day' => 10,
        'owner_user_id' => $user->id,
        'is_shared' => true,
    ]);
    $card->users()->sync([$user->id]);

    CreditCardStatement::create([
        'credit_card_id' => $card->id,
        'year' => 2025,
        'month' => 8,
        'period_start' => '2025-08-01',
        'period_end' => '2025-08-31',
        'closing_day' => 5,
        'due_day' => 10,
        'status' => 'open',
    ]);

    $priorities = collect(app(FinancialAlertService::class)->collect(2025, 8, $user))->pluck('priority')->all();

    expect($priorities[0])->toBeGreaterThanOrEqual($priorities[count($priorities) - 1]);
});

it('limita alertas visiveis no dashboard a 5', function () {
    Carbon::setTestNow(Carbon::create(2025, 8, 15));
    $user = User::factory()->create();
    ['category' => $category, 'expenseType' => $expenseType, 'paymentMethod' => $paymentMethod] = alertFixtures($user);

    for ($i = 1; $i <= 6; $i++) {
        CategoryBudget::create([
            'user_id' => $user->id,
            'category_id' => Category::create(['name' => "Cat {$i}", 'slug' => "cat-{$i}"])->id,
            'amount' => 100,
        ]);

        $tx = Transaction::create([
            'description' => "Gasto {$i}",
            'amount' => 90,
            'total_amount' => 90,
            'transaction_date' => '2025-08-10',
            'due_date' => '2025-08-10',
            'category_id' => Category::where('slug', "cat-{$i}")->first()->id,
            'type_id' => $expenseType->id,
            'payment_method_id' => $paymentMethod->id,
        ]);
        $tx->users()->sync([$user->id]);
    }

    $dashboard = app(FinancialAlertService::class)->forDashboard(2025, 8, $user);

    expect($dashboard['visible_count'])->toBe(5)
        ->and($dashboard['has_more'])->toBeTrue()
        ->and($dashboard['more_count'])->toBeGreaterThan(0);
});

it('nao inclui transacoes de outros usuarios nos alertas', function () {
    Carbon::setTestNow(Carbon::create(2025, 8, 15));
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    ['category' => $category, 'expenseType' => $expenseType, 'paymentMethod' => $paymentMethod] = alertFixtures($userA);

    CategoryBudget::create(['user_id' => $userB->id, 'category_id' => $category->id, 'amount' => 100]);

    $tx = Transaction::create([
        'description' => 'Gasto B',
        'amount' => 500,
        'total_amount' => 500,
        'transaction_date' => '2025-08-10',
        'due_date' => '2025-08-10',
        'category_id' => $category->id,
        'type_id' => $expenseType->id,
        'payment_method_id' => $paymentMethod->id,
    ]);
    $tx->users()->sync([$userB->id]);

    $alerts = app(FinancialAlertService::class)->collect(2025, 8, $userA);

    expect($alerts)->toBeEmpty();
});

it('export json usa a mesma fonte de alertas', function () {
    Carbon::setTestNow(Carbon::create(2025, 8, 15));
    $user = User::factory()->create();
    ['category' => $category, 'expenseType' => $expenseType, 'paymentMethod' => $paymentMethod] = alertFixtures($user);

    CategoryBudget::create(['user_id' => $user->id, 'category_id' => $category->id, 'amount' => 200]);

    $tx = Transaction::create([
        'description' => 'Gasto',
        'amount' => 180,
        'total_amount' => 180,
        'transaction_date' => '2025-08-10',
        'due_date' => '2025-08-10',
        'category_id' => $category->id,
        'type_id' => $expenseType->id,
        'payment_method_id' => $paymentMethod->id,
    ]);
    $tx->users()->sync([$user->id]);

    $response = $this->actingAs($user)->get('/export/json');

    $response->assertOk();
    $payload = $response->json();

    expect($payload['alertas'])->not->toBeEmpty()
        ->and($payload['alertas'][0])->toHaveKeys(['tipo', 'mensagem']);
});

it('expoe alertas na api do dashboard mensal', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson('/api/dashboard/monthly?year='.now()->year.'&month='.now()->month);

    $response->assertOk()->assertJsonStructure([
        'alerts' => [
            'items',
            'total',
            'visible_count',
            'has_more',
            'more_count',
        ],
    ]);
});

afterEach(function () {
    Carbon::setTestNow();
});
