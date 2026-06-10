<?php

use App\Models\Category;
use App\Models\CreditCard;
use App\Models\PaymentMethod;
use App\Models\RecurringTransaction;
use App\Models\Transaction;
use App\Models\TransactionInstallment;
use App\Models\Type;
use App\Models\User;
use App\Services\FutureCommitmentService;
use App\Services\MonthlyDashboardService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

function createFutureCommitmentFixtures(User $user): array
{
    $category = Category::create(['name' => 'Gerais FC', 'slug' => 'gerais-fc']);
    $expenseType = Type::create(['name' => 'Despesa', 'slug' => 'dc']);
    $incomeType = Type::create(['name' => 'Receita', 'slug' => 'rc']);
    $paymentMethod = PaymentMethod::create(['name' => 'Pix', 'slug' => 'pix-fc']);

    return compact('category', 'expenseType', 'incomeType', 'paymentMethod');
}

it('retorna proximos 3 meses a partir do mes selecionado', function () {
    $user = User::factory()->create();
    $service = app(FutureCommitmentService::class);

    $data = $service->forDashboard(2026, 6, $user);

    expect($data['range'])->toBe(3);
    expect($data['months'])->toHaveCount(3);
    expect($data['months'][0]['month'])->toBe('2026-07');
    expect($data['months'][1]['month'])->toBe('2026-08');
    expect($data['months'][2]['month'])->toBe('2026-09');
    expect($data['months'][0]['label'])->toBe('Julho/2026');
});

it('soma parcelas futuras no mes correto', function () {
    $user = User::factory()->create();
    ['category' => $category, 'expenseType' => $expenseType, 'paymentMethod' => $paymentMethod] = createFutureCommitmentFixtures($user);

    $transaction = Transaction::create([
        'description' => 'Financiamento',
        'amount' => 300.00,
        'total_amount' => 1200.00,
        'transaction_date' => '2026-05-10',
        'due_date' => '2026-05-10',
        'category_id' => $category->id,
        'type_id' => $expenseType->id,
        'payment_method_id' => $paymentMethod->id,
        'installment_total' => 4,
    ]);
    $transaction->users()->sync([$user->id]);

    TransactionInstallment::create([
        'transaction_id' => $transaction->id,
        'installment_number' => 1,
        'installment_total' => 4,
        'amount' => 300.00,
        'year' => 2026,
        'month' => 7,
        'due_date' => '2026-07-10',
    ]);

    TransactionInstallment::create([
        'transaction_id' => $transaction->id,
        'installment_number' => 2,
        'installment_total' => 4,
        'amount' => 300.00,
        'year' => 2026,
        'month' => 8,
        'due_date' => '2026-08-10',
    ]);

    $data = app(FutureCommitmentService::class)->forDashboard(2026, 6, $user);

    expect($data['months'][0]['installments_total'])->toBe(300.0);
    expect($data['months'][1]['installments_total'])->toBe(300.0);
    expect($data['months'][2]['installments_total'])->toBe(0.0);
    expect($data['months'][0]['total'])->toBe(300.0);
});

it('soma contas fixas previstas no mes correto', function () {
    $user = User::factory()->create();
    ['category' => $category, 'expenseType' => $expenseType, 'paymentMethod' => $paymentMethod] = createFutureCommitmentFixtures($user);

    $template = RecurringTransaction::create([
        'description' => 'Aluguel',
        'amount' => 1500.00,
        'total_amount' => 1500.00,
        'frequency' => 'monthly',
        'day_of_month' => 5,
        'start_date' => Carbon::create(2026, 1, 1),
        'is_active' => true,
        'category_id' => $category->id,
        'type_id' => $expenseType->id,
        'payment_method_id' => $paymentMethod->id,
    ]);
    $template->users()->sync([$user->id]);

    $data = app(FutureCommitmentService::class)->forDashboard(2026, 6, $user);

    expect($data['months'][0]['recurring_total'])->toBe(1500.0);
    expect($data['months'][1]['recurring_total'])->toBe(1500.0);
    expect($data['months'][2]['recurring_total'])->toBe(1500.0);
    expect($data['months'][0]['total'])->toBe(1500.0);
    expect($data['months'][0]['is_estimated'])->toBeTrue();
});

it('conta fixa ja materializada nao duplica', function () {
    $user = User::factory()->create();
    ['category' => $category, 'expenseType' => $expenseType, 'paymentMethod' => $paymentMethod] = createFutureCommitmentFixtures($user);

    $dueDate = Carbon::create(2026, 7, 5);

    $template = RecurringTransaction::create([
        'description' => 'Internet',
        'amount' => 120.00,
        'total_amount' => 120.00,
        'frequency' => 'monthly',
        'day_of_month' => 5,
        'start_date' => Carbon::create(2026, 1, 1),
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

    $month = app(FutureCommitmentService::class)->commitmentsForMonth(2026, 7, $user);

    expect($month['recurring_total'])->toBe(0.0);
    expect($month['total'])->toBe(0.0);
});

it('mes sem compromissos retorna total zero', function () {
    $user = User::factory()->create();

    $data = app(FutureCommitmentService::class)->forDashboard(2026, 6, $user);

    foreach ($data['months'] as $month) {
        expect($month['total'])->toBe(0.0);
        expect($month['installments_total'])->toBe(0.0);
        expect($month['recurring_total'])->toBe(0.0);
    }

    expect($data['has_commitments'])->toBeFalse();
});

it('respeita escopo do usuario e rede', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    DB::table('user_relations')->insert([
        ['user_id' => $userA->id, 'related_user_id' => $userB->id, 'relation_type' => 'partner', 'created_at' => now(), 'updated_at' => now()],
        ['user_id' => $userB->id, 'related_user_id' => $userA->id, 'relation_type' => 'partner', 'created_at' => now(), 'updated_at' => now()],
    ]);

    ['category' => $category, 'expenseType' => $expenseType, 'paymentMethod' => $paymentMethod] = createFutureCommitmentFixtures($userA);

    $transaction = Transaction::create([
        'description' => 'Parcela compartilhada',
        'amount' => 200.00,
        'total_amount' => 600.00,
        'transaction_date' => '2026-06-01',
        'due_date' => '2026-06-01',
        'category_id' => $category->id,
        'type_id' => $expenseType->id,
        'payment_method_id' => $paymentMethod->id,
        'installment_total' => 3,
    ]);
    $transaction->users()->sync([$userA->id, $userB->id]);

    TransactionInstallment::create([
        'transaction_id' => $transaction->id,
        'installment_number' => 1,
        'installment_total' => 3,
        'amount' => 200.00,
        'year' => 2026,
        'month' => 7,
        'due_date' => '2026-07-15',
    ]);

    $dataA = app(FutureCommitmentService::class)->forDashboard(2026, 6, $userA);
    $dataB = app(FutureCommitmentService::class)->forDashboard(2026, 6, $userB);

    expect($dataA['months'][0]['installments_total'])->toBe(200.0);
    expect($dataB['months'][0]['installments_total'])->toBe(200.0);
});

it('nao inclui compromissos de outro usuario fora da rede', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    ['category' => $category, 'expenseType' => $expenseType, 'paymentMethod' => $paymentMethod] = createFutureCommitmentFixtures($userA);

    $transaction = Transaction::create([
        'description' => 'Parcela externa',
        'amount' => 500.00,
        'total_amount' => 500.00,
        'transaction_date' => '2026-06-01',
        'due_date' => '2026-06-01',
        'category_id' => $category->id,
        'type_id' => $expenseType->id,
        'payment_method_id' => $paymentMethod->id,
        'installment_total' => 1,
    ]);
    $transaction->users()->sync([$userB->id]);

    TransactionInstallment::create([
        'transaction_id' => $transaction->id,
        'installment_number' => 1,
        'installment_total' => 1,
        'amount' => 500.00,
        'year' => 2026,
        'month' => 7,
        'due_date' => '2026-07-10',
    ]);

    $data = app(FutureCommitmentService::class)->forDashboard(2026, 6, $userA);

    expect($data['months'][0]['installments_total'])->toBe(0.0);
    expect($data['has_commitments'])->toBeFalse();
});

it('nao altera o saldo projetado existente', function () {
    $user = User::factory()->create();
    ['category' => $category, 'expenseType' => $expenseType, 'incomeType' => $incomeType, 'paymentMethod' => $paymentMethod] = createFutureCommitmentFixtures($user);

    $year = 2026;
    $month = 6;
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

    $template = RecurringTransaction::create([
        'description' => 'Academia',
        'amount' => 100.00,
        'total_amount' => 100.00,
        'frequency' => 'monthly',
        'day_of_month' => 10,
        'start_date' => Carbon::create(2026, 1, 1),
        'is_active' => true,
        'category_id' => $category->id,
        'type_id' => $expenseType->id,
        'payment_method_id' => $paymentMethod->id,
    ]);
    $template->users()->sync([$user->id]);

    $data = app(MonthlyDashboardService::class)->build($year, $month, $user);
    $projected = $data['cards']['projected_balance'];

    expect($projected['amount'])->toBe(2900.0);
    expect($data)->toHaveKey('future_commitments');
    expect($data['future_commitments']['months'][0]['recurring_total'])->toBe(100.0);
});

it('expoe future_commitments na api do dashboard mensal', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson('/api/dashboard/monthly?year=2026&month=6');

    $response->assertOk()
        ->assertJsonStructure([
            'future_commitments' => [
                'months' => [
                    '*' => [
                        'month',
                        'label',
                        'total',
                        'installments_total',
                        'recurring_total',
                        'card_statements_total',
                        'items_count',
                        'is_estimated',
                    ],
                ],
                'range',
                'has_commitments',
                'includes_card_statements',
                'note',
            ],
        ]);
});

it('nao duplica parcelas de cartao com fatura separada', function () {
    $user = User::factory()->create();
    ['category' => $category, 'expenseType' => $expenseType, 'paymentMethod' => $paymentMethod] = createFutureCommitmentFixtures($user);

    $card = CreditCard::create([
        'name' => 'Visa',
        'closing_day' => 5,
        'due_day' => 12,
        'owner_user_id' => $user->id,
        'is_shared' => true,
    ]);
    $card->users()->sync([$user->id]);

    $transaction = Transaction::create([
        'description' => 'Compra parcelada',
        'amount' => 250.00,
        'total_amount' => 750.00,
        'transaction_date' => '2026-06-01',
        'due_date' => '2026-06-01',
        'category_id' => $category->id,
        'type_id' => $expenseType->id,
        'payment_method_id' => $paymentMethod->id,
        'credit_card_id' => $card->id,
        'installment_total' => 3,
    ]);
    $transaction->users()->sync([$user->id]);

    $month = app(FutureCommitmentService::class)->commitmentsForMonth(2026, 7, $user);

    expect($month['installments_total'])->toBe(250.0);
    expect($month['card_statements_total'])->toBe(0.0);
    expect($month['total'])->toBe(250.0);
    expect($month['total'])->toBe($month['installments_total']);
});

it('combina parcelas e contas fixas no total do mes', function () {
    $user = User::factory()->create();
    ['category' => $category, 'expenseType' => $expenseType, 'paymentMethod' => $paymentMethod] = createFutureCommitmentFixtures($user);

    $transaction = Transaction::create([
        'description' => 'Parcela',
        'amount' => 400.00,
        'total_amount' => 400.00,
        'transaction_date' => '2026-06-01',
        'due_date' => '2026-06-01',
        'category_id' => $category->id,
        'type_id' => $expenseType->id,
        'payment_method_id' => $paymentMethod->id,
        'installment_total' => 1,
    ]);
    $transaction->users()->sync([$user->id]);

    TransactionInstallment::create([
        'transaction_id' => $transaction->id,
        'installment_number' => 1,
        'installment_total' => 1,
        'amount' => 400.00,
        'year' => 2026,
        'month' => 7,
        'due_date' => '2026-07-20',
    ]);

    $template = RecurringTransaction::create([
        'description' => 'Streaming',
        'amount' => 55.00,
        'total_amount' => 55.00,
        'frequency' => 'monthly',
        'day_of_month' => 10,
        'start_date' => Carbon::create(2026, 1, 1),
        'is_active' => true,
        'category_id' => $category->id,
        'type_id' => $expenseType->id,
        'payment_method_id' => $paymentMethod->id,
    ]);
    $template->users()->sync([$user->id]);

    $month = app(FutureCommitmentService::class)->commitmentsForMonth(2026, 7, $user);

    expect($month['installments_total'])->toBe(400.0);
    expect($month['recurring_total'])->toBe(55.0);
    expect($month['total'])->toBe(455.0);
    expect($month['items_count'])->toBe(2);
});
