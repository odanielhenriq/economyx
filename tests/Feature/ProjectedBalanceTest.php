<?php

use App\Models\Category;
use App\Models\CreditCard;
use App\Models\CreditCardStatement;
use App\Models\PaymentMethod;
use App\Models\RecurringTransaction;
use App\Models\Transaction;
use App\Models\TransactionInstallment;
use App\Models\Type;
use App\Models\User;
use App\Services\MonthlyDashboardService;
use Carbon\Carbon;

function createProjectedBalanceFixtures(User $user): array
{
    $category = Category::create(['name' => 'Gerais', 'slug' => 'gerais-pb']);
    $expenseType = Type::create(['name' => 'Despesa', 'slug' => 'dc']);
    $incomeType = Type::create(['name' => 'Receita', 'slug' => 'rc']);
    $paymentMethod = PaymentMethod::create(['name' => 'Dinheiro', 'slug' => 'cash-pb']);

    return compact('category', 'expenseType', 'incomeType', 'paymentMethod');
}

it('calcula saldo projetado positivo quando receitas superam saidas previstas', function () {
    $user = User::factory()->create();
    ['category' => $category, 'expenseType' => $expenseType, 'incomeType' => $incomeType, 'paymentMethod' => $paymentMethod] = createProjectedBalanceFixtures($user);

    $year = 2025;
    $month = 8;
    $baseDate = Carbon::create($year, $month, 10)->toDateString();

    $income = Transaction::create([
        'description' => 'Salario',
        'amount' => 3000.00,
        'total_amount' => 3000.00,
        'transaction_date' => $baseDate,
        'due_date' => $baseDate,
        'category_id' => $category->id,
        'type_id' => $incomeType->id,
        'payment_method_id' => $paymentMethod->id,
    ]);
    $income->users()->sync([$user->id]);

    $expense = Transaction::create([
        'description' => 'Mercado',
        'amount' => 400.00,
        'total_amount' => 400.00,
        'transaction_date' => $baseDate,
        'due_date' => $baseDate,
        'category_id' => $category->id,
        'type_id' => $expenseType->id,
        'payment_method_id' => $paymentMethod->id,
    ]);
    $expense->users()->sync([$user->id]);

    $data = app(MonthlyDashboardService::class)->build($year, $month, $user);
    $projected = $data['cards']['projected_balance'];

    expect($projected['amount'])->toBe(2600.0);
    expect($projected['is_negative'])->toBeFalse();
    expect($projected['income'])->toBe(3000.0);
    expect($projected['expenses_recorded'])->toBe(400.0);
    expect($projected['payable'])->toBe(0.0);
    expect($projected['recurring_projection'])->toBe(0.0);
    expect($projected['amount'])->toBe($data['cards']['balance_month']);
});

it('calcula saldo projetado negativo quando saidas previstas superam receitas', function () {
    $user = User::factory()->create();
    ['category' => $category, 'expenseType' => $expenseType, 'incomeType' => $incomeType, 'paymentMethod' => $paymentMethod] = createProjectedBalanceFixtures($user);

    $year = 2025;
    $month = 9;
    $baseDate = Carbon::create($year, $month, 5)->toDateString();

    $income = Transaction::create([
        'description' => 'Freela',
        'amount' => 400.00,
        'total_amount' => 400.00,
        'transaction_date' => $baseDate,
        'due_date' => $baseDate,
        'category_id' => $category->id,
        'type_id' => $incomeType->id,
        'payment_method_id' => $paymentMethod->id,
    ]);
    $income->users()->sync([$user->id]);

    $expense = Transaction::create([
        'description' => 'Aluguel',
        'amount' => 150.00,
        'total_amount' => 150.00,
        'transaction_date' => $baseDate,
        'due_date' => $baseDate,
        'category_id' => $category->id,
        'type_id' => $expenseType->id,
        'payment_method_id' => $paymentMethod->id,
    ]);
    $expense->users()->sync([$user->id]);

    $card = CreditCard::create([
        'name' => 'Visa',
        'closing_day' => 5,
        'due_day' => 12,
        'owner_user_id' => $user->id,
        'is_shared' => true,
    ]);
    $card->users()->sync([$user->id]);

    $cardTransaction = Transaction::create([
        'description' => 'Compra parcelada',
        'amount' => 350.00,
        'total_amount' => 350.00,
        'transaction_date' => $baseDate,
        'due_date' => $baseDate,
        'category_id' => $category->id,
        'type_id' => $expenseType->id,
        'payment_method_id' => $paymentMethod->id,
        'credit_card_id' => $card->id,
    ]);
    $cardTransaction->users()->sync([$user->id]);

    $statement = CreditCardStatement::create([
        'credit_card_id' => $card->id,
        'year' => $year,
        'month' => $month,
        'period_start' => Carbon::create($year, $month, 1)->startOfMonth()->toDateString(),
        'period_end' => Carbon::create($year, $month, 1)->endOfMonth()->toDateString(),
        'closing_day' => 5,
        'due_day' => 12,
        'status' => 'open',
    ]);

    TransactionInstallment::create([
        'transaction_id' => $cardTransaction->id,
        'credit_card_statement_id' => $statement->id,
        'installment_number' => 1,
        'installment_total' => 1,
        'amount' => 350.00,
        'year' => $year,
        'month' => $month,
        'due_date' => Carbon::create($year, $month, 12)->toDateString(),
    ]);

    $data = app(MonthlyDashboardService::class)->build($year, $month, $user);
    $projected = $data['cards']['projected_balance'];

    expect($projected['amount'])->toBe(-100.0);
    expect($projected['is_negative'])->toBeTrue();
    expect($projected['expenses_recorded'])->toBe(150.0);
    expect($projected['payable'])->toBe(350.0);
});

it('nao conta conta fixa duas vezes quando ja materializada no mes', function () {
    $user = User::factory()->create();
    ['category' => $category, 'expenseType' => $expenseType, 'incomeType' => $incomeType, 'paymentMethod' => $paymentMethod] = createProjectedBalanceFixtures($user);

    $year = 2025;
    $month = 10;
    $dueDate = Carbon::create($year, $month, 10);

    $template = RecurringTransaction::create([
        'description' => 'Internet',
        'amount' => 120.00,
        'total_amount' => 120.00,
        'frequency' => 'monthly',
        'day_of_month' => 10,
        'start_date' => $dueDate->copy()->subYear(),
        'is_active' => true,
        'category_id' => $category->id,
        'type_id' => $expenseType->id,
        'payment_method_id' => $paymentMethod->id,
    ]);
    $template->users()->sync([$user->id]);

    $materialized = Transaction::create([
        'description' => 'Internet',
        'amount' => 120.00,
        'total_amount' => 120.00,
        'transaction_date' => $dueDate->toDateString(),
        'due_date' => $dueDate->toDateString(),
        'category_id' => $category->id,
        'type_id' => $expenseType->id,
        'payment_method_id' => $paymentMethod->id,
        'recurring_transaction_id' => $template->id,
    ]);
    $materialized->users()->sync([$user->id]);

    $income = Transaction::create([
        'description' => 'Salario',
        'amount' => 2000.00,
        'total_amount' => 2000.00,
        'transaction_date' => $dueDate->toDateString(),
        'due_date' => $dueDate->toDateString(),
        'category_id' => $category->id,
        'type_id' => $incomeType->id,
        'payment_method_id' => $paymentMethod->id,
    ]);
    $income->users()->sync([$user->id]);

    $data = app(MonthlyDashboardService::class)->build($year, $month, $user);
    $projected = $data['cards']['projected_balance'];

    expect($projected['recurring_projection'])->toBe(0.0);
    expect($projected['expenses_recorded'])->toBe(120.0);
    expect($projected['amount'])->toBe(1880.0);
});

it('inclui conta fixa prevista quando ainda nao materializada', function () {
    $user = User::factory()->create();
    ['category' => $category, 'expenseType' => $expenseType, 'incomeType' => $incomeType, 'paymentMethod' => $paymentMethod] = createProjectedBalanceFixtures($user);

    $year = (int) now()->year;
    $month = (int) now()->month;
    $dueDate = Carbon::create($year, $month, 15);

    $template = RecurringTransaction::create([
        'description' => 'Academia',
        'amount' => 89.90,
        'total_amount' => 89.90,
        'frequency' => 'monthly',
        'day_of_month' => 15,
        'start_date' => $dueDate->copy()->subMonths(2),
        'is_active' => true,
        'category_id' => $category->id,
        'type_id' => $expenseType->id,
        'payment_method_id' => $paymentMethod->id,
    ]);
    $template->users()->sync([$user->id]);

    $income = Transaction::create([
        'description' => 'Salario',
        'amount' => 1000.00,
        'total_amount' => 1000.00,
        'transaction_date' => $dueDate->toDateString(),
        'due_date' => $dueDate->toDateString(),
        'category_id' => $category->id,
        'type_id' => $incomeType->id,
        'payment_method_id' => $paymentMethod->id,
    ]);
    $income->users()->sync([$user->id]);

    $data = app(MonthlyDashboardService::class)->build($year, $month, $user);
    $projected = $data['cards']['projected_balance'];

    expect($projected['recurring_projection'])->toBe(89.9);
    expect($projected['expenses_recorded'])->toBe(0.0);
    expect($projected['amount'])->toBe(910.1);
});

it('nao duplica parcela de cartao entre despesas lançadas e a pagar', function () {
    $user = User::factory()->create();
    ['category' => $category, 'expenseType' => $expenseType, 'paymentMethod' => $paymentMethod] = createProjectedBalanceFixtures($user);

    $year = 2025;
    $month = 11;
    $baseDate = Carbon::create($year, $month, 8)->toDateString();

    $card = CreditCard::create([
        'name' => 'Master',
        'closing_day' => 3,
        'due_day' => 10,
        'owner_user_id' => $user->id,
        'is_shared' => true,
    ]);
    $card->users()->sync([$user->id]);

    $cardTransaction = Transaction::create([
        'description' => 'Eletrônicos',
        'amount' => 250.00,
        'total_amount' => 250.00,
        'transaction_date' => $baseDate,
        'due_date' => $baseDate,
        'category_id' => $category->id,
        'type_id' => $expenseType->id,
        'payment_method_id' => $paymentMethod->id,
        'credit_card_id' => $card->id,
    ]);
    $cardTransaction->users()->sync([$user->id]);

    $statement = CreditCardStatement::create([
        'credit_card_id' => $card->id,
        'year' => $year,
        'month' => $month,
        'period_start' => Carbon::create($year, $month, 1)->startOfMonth()->toDateString(),
        'period_end' => Carbon::create($year, $month, 1)->endOfMonth()->toDateString(),
        'closing_day' => 3,
        'due_day' => 10,
        'status' => 'open',
    ]);

    TransactionInstallment::create([
        'transaction_id' => $cardTransaction->id,
        'credit_card_statement_id' => $statement->id,
        'installment_number' => 1,
        'installment_total' => 3,
        'amount' => 250.00,
        'year' => $year,
        'month' => $month,
        'due_date' => Carbon::create($year, $month, 10)->toDateString(),
    ]);

    $data = app(MonthlyDashboardService::class)->build($year, $month, $user);
    $projected = $data['cards']['projected_balance'];

    expect($projected['expenses_recorded'])->toBe(0.0);
    expect($projected['payable'])->toBe(250.0);
    expect($projected['amount'])->toBe(-250.0);
    expect($projected['is_negative'])->toBeTrue();
});

it('respeita o mes selecionado no calculo', function () {
    $user = User::factory()->create();
    ['category' => $category, 'expenseType' => $expenseType, 'paymentMethod' => $paymentMethod] = createProjectedBalanceFixtures($user);

    $template = RecurringTransaction::create([
        'description' => 'Assinatura',
        'amount' => 50.00,
        'total_amount' => 50.00,
        'frequency' => 'monthly',
        'day_of_month' => 10,
        'start_date' => Carbon::create(2025, 6, 1),
        'is_active' => true,
        'category_id' => $category->id,
        'type_id' => $expenseType->id,
        'payment_method_id' => $paymentMethod->id,
    ]);
    $template->users()->sync([$user->id]);

    $june = app(MonthlyDashboardService::class)->build(2025, 6, $user);
    $july = app(MonthlyDashboardService::class)->build(2025, 7, $user);

    expect($june['cards']['projected_balance']['recurring_projection'])->toBe(50.0);
    expect($july['cards']['projected_balance']['recurring_projection'])->toBe(50.0);
});

it('nao inclui transacoes de outros usuarios no saldo projetado', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    ['category' => $category, 'expenseType' => $expenseType, 'incomeType' => $incomeType, 'paymentMethod' => $paymentMethod] = createProjectedBalanceFixtures($userA);

    $year = 2025;
    $month = 12;
    $baseDate = Carbon::create($year, $month, 1)->toDateString();

    $txB = Transaction::create([
        'description' => 'Receita B',
        'amount' => 9000.00,
        'total_amount' => 9000.00,
        'transaction_date' => $baseDate,
        'due_date' => $baseDate,
        'category_id' => $category->id,
        'type_id' => $incomeType->id,
        'payment_method_id' => $paymentMethod->id,
    ]);
    $txB->users()->sync([$userB->id]);

    $data = app(MonthlyDashboardService::class)->build($year, $month, $userA);
    $projected = $data['cards']['projected_balance'];

    expect($projected['income'])->toBe(0.0);
    expect($projected['amount'])->toBe(0.0);
});

it('expoe saldo projetado na api do dashboard', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson('/api/dashboard/monthly?year=2025&month=1');

    $response->assertOk()
        ->assertJsonStructure([
            'cards' => [
                'projected_balance' => [
                    'amount',
                    'income',
                    'expenses_recorded',
                    'payable',
                    'recurring_projection',
                    'is_negative',
                ],
            ],
        ]);
});
