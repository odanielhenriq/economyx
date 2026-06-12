<?php

use App\Models\Category;
use App\Models\CreditCard;
use App\Models\CreditCardStatement;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Models\TransactionInstallment;
use App\Models\Type;
use App\Models\User;
use App\Services\InstallmentPurchaseService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

function installmentPurchaseFixtures(): array
{
    $category = Category::create(['name' => 'Eletrônicos', 'slug' => 'eletronicos-ip']);
    $expenseType = Type::create(['name' => 'Despesa', 'slug' => 'dc-ip']);
    $paymentMethod = PaymentMethod::create(['name' => 'Cartão', 'slug' => 'cartao-ip']);

    return compact('category', 'expenseType', 'paymentMethod');
}

function createInstallmentPurchase(
    User $user,
    array $fixtures,
    string $description,
    float $installmentAmount,
    int $totalInstallments,
    string $purchaseDate,
    array $installmentDueDates,
    ?CreditCard $card = null,
    ?int $startInstallmentNumber = null
): Transaction {
    ['category' => $category, 'expenseType' => $expenseType, 'paymentMethod' => $paymentMethod] = $fixtures;

    $transaction = Transaction::withoutEvents(fn () => Transaction::create([
        'description' => $description,
        'amount' => $installmentAmount,
        'total_amount' => round($installmentAmount * $totalInstallments, 2),
        'transaction_date' => $purchaseDate,
        'due_date' => $installmentDueDates[0] ?? $purchaseDate,
        'category_id' => $category->id,
        'type_id' => $expenseType->id,
        'payment_method_id' => $paymentMethod->id,
        'credit_card_id' => $card?->id,
        'installment_total' => $totalInstallments,
        'installment_number' => $startInstallmentNumber,
    ]));
    $transaction->users()->sync([$user->id]);

    foreach ($installmentDueDates as $index => $dueDate) {
        $due = Carbon::parse($dueDate);
        $installmentNumber = ($startInstallmentNumber ?: 1) + $index;

        TransactionInstallment::create([
            'transaction_id' => $transaction->id,
            'credit_card_statement_id' => null,
            'installment_number' => $installmentNumber,
            'installment_total' => $totalInstallments,
            'amount' => $installmentAmount,
            'year' => $due->year,
            'month' => $due->month,
            'due_date' => $dueDate,
        ]);
    }

    return $transaction->fresh(['installments', 'category', 'creditCard']);
}

it('usuario ve compra parcelada ativa', function () {
    Carbon::setTestNow('2026-06-15');
    $user = User::factory()->create();
    $fixtures = installmentPurchaseFixtures();

    createInstallmentPurchase(
        $user,
        $fixtures,
        'Notebook',
        250.00,
        10,
        '2026-03-10',
        ['2026-04-10', '2026-05-10', '2026-06-10', '2026-07-10', '2026-08-10', '2026-09-10', '2026-10-10', '2026-11-10', '2026-12-10', '2027-01-10']
    );

    $data = app(InstallmentPurchaseService::class)->forUser($user);

    expect($data['items'])->toHaveCount(1);
    expect($data['items'][0]['description'])->toBe('Notebook');
    expect($data['items'][0]['status'])->not->toBe('completed');
});

it('compra a vista nao aparece', function () {
    Carbon::setTestNow('2026-06-15');
    $user = User::factory()->create();
    $fixtures = installmentPurchaseFixtures();
    ['category' => $category, 'expenseType' => $expenseType, 'paymentMethod' => $paymentMethod] = $fixtures;

    $transaction = Transaction::create([
        'description' => 'Compra avista',
        'amount' => 500.00,
        'total_amount' => 500.00,
        'transaction_date' => '2026-06-01',
        'due_date' => '2026-06-10',
        'category_id' => $category->id,
        'type_id' => $expenseType->id,
        'payment_method_id' => $paymentMethod->id,
        'installment_total' => 1,
    ]);
    $transaction->users()->sync([$user->id]);

    $data = app(InstallmentPurchaseService::class)->forUser($user);

    expect($data['items'])->toBeEmpty();
});

it('compra parcelada quitada nao aparece no filtro ativas', function () {
    Carbon::setTestNow('2027-02-15');
    $user = User::factory()->create();
    $fixtures = installmentPurchaseFixtures();

    createInstallmentPurchase(
        $user,
        $fixtures,
        'Curso',
        100.00,
        4,
        '2026-06-01',
        ['2026-07-10', '2026-08-10', '2026-09-10', '2026-10-10']
    );

    $data = app(InstallmentPurchaseService::class)->forUser($user, ['status' => 'active']);

    expect($data['items'])->toBeEmpty();
});

it('filtro todas mostra ativas e quitadas', function () {
    Carbon::setTestNow('2026-08-15');
    $user = User::factory()->create();
    $fixtures = installmentPurchaseFixtures();

    createInstallmentPurchase(
        $user,
        $fixtures,
        'Notebook',
        250.00,
        4,
        '2026-03-10',
        ['2026-04-10', '2026-05-10', '2026-06-10', '2026-07-10']
    );

    createInstallmentPurchase(
        $user,
        $fixtures,
        'Curso quitado',
        100.00,
        2,
        '2026-01-01',
        ['2026-02-10', '2026-03-10']
    );

    $data = app(InstallmentPurchaseService::class)->forUser($user, ['status' => 'all']);

    expect($data['items'])->toHaveCount(2);
});

it('calcula quantidade total de parcelas', function () {
    Carbon::setTestNow('2026-06-15');
    $user = User::factory()->create();
    $fixtures = installmentPurchaseFixtures();

    createInstallmentPurchase(
        $user,
        $fixtures,
        'Celular',
        150.00,
        12,
        '2026-01-10',
        collect(range(0, 11))->map(fn ($i) => Carbon::parse('2026-02-10')->addMonths($i)->format('Y-m-d'))->all()
    );

    $item = app(InstallmentPurchaseService::class)->forUser($user)['items'][0];

    expect($item['total_installments'])->toBe(12);
});

it('calcula parcelas restantes', function () {
    Carbon::setTestNow('2026-06-15');
    $user = User::factory()->create();
    $fixtures = installmentPurchaseFixtures();

    createInstallmentPurchase(
        $user,
        $fixtures,
        'Notebook',
        250.00,
        10,
        '2026-03-10',
        ['2026-04-10', '2026-05-10', '2026-06-10', '2026-07-10', '2026-08-10', '2026-09-10', '2026-10-10', '2026-11-10', '2026-12-10', '2027-01-10']
    );

    $item = app(InstallmentPurchaseService::class)->forUser($user)['items'][0];

    expect($item['remaining_installments'])->toBe(7);
    expect($item['current_installment'])->toBe(4);
});

it('calcula valor restante', function () {
    Carbon::setTestNow('2026-06-15');
    $user = User::factory()->create();
    $fixtures = installmentPurchaseFixtures();

    createInstallmentPurchase(
        $user,
        $fixtures,
        'Notebook',
        250.00,
        10,
        '2026-03-10',
        ['2026-04-10', '2026-05-10', '2026-06-10', '2026-07-10', '2026-08-10', '2026-09-10', '2026-10-10', '2026-11-10', '2026-12-10', '2027-01-10']
    );

    $item = app(InstallmentPurchaseService::class)->forUser($user)['items'][0];

    expect($item['remaining_amount'])->toBe(1750.0);
});

it('calcula valor restante em financiamento cadastrado no meio do prazo', function () {
    Carbon::setTestNow('2026-05-15');
    $user = User::factory()->create();
    $fixtures = installmentPurchaseFixtures();

    $installmentAmount = 457.57;
    $totalInstallments = 36;
    $startInstallmentNumber = 23;
    $remainingRecords = $totalInstallments - $startInstallmentNumber + 1;
    $dueDates = collect(range(0, $remainingRecords - 1))
        ->map(fn (int $i) => Carbon::parse('2026-02-10')->addMonthsNoOverflow($i)->format('Y-m-d'))
        ->all();

    createInstallmentPurchase(
        $user,
        $fixtures,
        'Financiamento moto',
        $installmentAmount,
        $totalInstallments,
        '2024-03-13',
        $dueDates,
        null,
        $startInstallmentNumber
    );

    $item = app(InstallmentPurchaseService::class)->forUser($user)['items'][0];

    expect($item['current_installment'])->toBe(27);
    expect($item['remaining_installments'])->toBe(10);
    expect($item['remaining_amount'])->toBe(4575.7);
});

it('calcula proxima parcela', function () {
    Carbon::setTestNow('2026-06-15');
    $user = User::factory()->create();
    $fixtures = installmentPurchaseFixtures();

    createInstallmentPurchase(
        $user,
        $fixtures,
        'Notebook',
        250.00,
        10,
        '2026-03-10',
        ['2026-04-10', '2026-05-10', '2026-06-10', '2026-07-10', '2026-08-10', '2026-09-10', '2026-10-10', '2026-11-10', '2026-12-10', '2027-01-10']
    );

    $data = app(InstallmentPurchaseService::class)->forUser($user);

    expect($data['items'][0]['next_due_date'])->toBe('2026-07-10');
    expect($data['summary']['next_installment']['due_date'])->toBe('2026-07-10');
    expect($data['summary']['next_installment']['amount'])->toBe(250.0);
});

it('identifica compra finalizando', function () {
    Carbon::setTestNow('2026-06-15');
    $user = User::factory()->create();
    $fixtures = installmentPurchaseFixtures();

    createInstallmentPurchase(
        $user,
        $fixtures,
        'Curso',
        100.00,
        4,
        '2026-03-10',
        ['2026-04-10', '2026-05-10', '2026-06-10', '2026-07-05']
    );

    $item = app(InstallmentPurchaseService::class)->forUser($user)['items'][0];

    expect($item['status'])->toBe('ending');
    expect($item['status_label'])->toBe('Finalizando');
});

it('respeita escopo de usuario e rede', function () {
    Carbon::setTestNow('2026-06-15');
    $userA = User::factory()->create(['name' => 'Daniel']);
    $userB = User::factory()->create(['name' => 'Joyce']);
    DB::table('user_relations')->insert([
        ['user_id' => $userA->id, 'related_user_id' => $userB->id, 'relation_type' => 'partner', 'created_at' => now(), 'updated_at' => now()],
        ['user_id' => $userB->id, 'related_user_id' => $userA->id, 'relation_type' => 'partner', 'created_at' => now(), 'updated_at' => now()],
    ]);
    $fixtures = installmentPurchaseFixtures();

    $transaction = createInstallmentPurchase(
        $userA,
        $fixtures,
        'Notebook compartilhado',
        200.00,
        5,
        '2026-03-10',
        ['2026-04-10', '2026-05-10', '2026-06-10', '2026-07-10', '2026-08-10']
    );
    $transaction->users()->sync([$userA->id, $userB->id]);

    $data = app(InstallmentPurchaseService::class)->forUser($userA);

    expect($data['items'])->toHaveCount(1);
});

it('nao mostra compra de usuario fora da rede', function () {
    Carbon::setTestNow('2026-06-15');
    $userA = User::factory()->create(['name' => 'Daniel']);
    $userB = User::factory()->create(['name' => 'Joyce']);
    $userC = User::factory()->create(['name' => 'Carlos']);
    DB::table('user_relations')->insert([
        ['user_id' => $userA->id, 'related_user_id' => $userB->id, 'relation_type' => 'partner', 'created_at' => now(), 'updated_at' => now()],
        ['user_id' => $userB->id, 'related_user_id' => $userA->id, 'relation_type' => 'partner', 'created_at' => now(), 'updated_at' => now()],
    ]);
    $fixtures = installmentPurchaseFixtures();

    $transaction = createInstallmentPurchase(
        $userC,
        $fixtures,
        'Compra externa',
        300.00,
        4,
        '2026-03-10',
        ['2026-04-10', '2026-05-10', '2026-06-10', '2026-07-10']
    );
    $transaction->users()->sync([$userB->id, $userC->id]);

    $data = app(InstallmentPurchaseService::class)->forUser($userA);

    expect($data['items'])->toBeEmpty();
});

it('compra no cartao mostra nome do cartao', function () {
    Carbon::setTestNow('2026-06-15');
    $user = User::factory()->create();
    $fixtures = installmentPurchaseFixtures();

    $card = CreditCard::create([
        'name' => 'Nubank',
        'closing_day' => 5,
        'due_day' => 12,
        'owner_user_id' => $user->id,
        'is_shared' => false,
    ]);

    createInstallmentPurchase(
        $user,
        $fixtures,
        'Notebook',
        250.00,
        4,
        '2026-03-10',
        ['2026-04-10', '2026-05-10', '2026-06-10', '2026-07-10'],
        $card
    );

    $item = app(InstallmentPurchaseService::class)->forUser($user)['items'][0];

    expect($item['card_name'])->toBe('Nubank');
});

it('compra sem cartao continua funcionando', function () {
    Carbon::setTestNow('2026-06-15');
    $user = User::factory()->create();
    $fixtures = installmentPurchaseFixtures();

    createInstallmentPurchase(
        $user,
        $fixtures,
        'Curso',
        100.00,
        4,
        '2026-03-10',
        ['2026-04-10', '2026-05-10', '2026-06-10', '2026-07-10']
    );

    $item = app(InstallmentPurchaseService::class)->forUser($user)['items'][0];

    expect($item['card_name'])->toBeNull();
    expect($item['description'])->toBe('Curso');
});

it('considera fatura paga como parcela quitada', function () {
    Carbon::setTestNow('2026-06-15');
    $user = User::factory()->create();
    $fixtures = installmentPurchaseFixtures();

    $card = CreditCard::create([
        'name' => 'Nubank',
        'closing_day' => 5,
        'due_day' => 12,
        'owner_user_id' => $user->id,
        'is_shared' => false,
    ]);

    $transaction = createInstallmentPurchase(
        $user,
        $fixtures,
        'Notebook',
        250.00,
        4,
        '2026-03-10',
        ['2026-07-10', '2026-08-10', '2026-09-10', '2026-10-10'],
        $card
    );

    $paidInstallment = $transaction->installments->firstWhere('installment_number', 1);
    $statement = CreditCardStatement::create([
        'credit_card_id' => $card->id,
        'year' => 2026,
        'month' => 7,
        'period_start' => '2026-06-06',
        'period_end' => '2026-07-05',
        'closing_day' => 5,
        'due_day' => 12,
        'status' => 'paid',
    ]);
    $paidInstallment->update(['credit_card_statement_id' => $statement->id]);
    $paidInstallment->load('statement');

    $item = app(InstallmentPurchaseService::class)->forUser($user)['items'][0];

    expect($item['remaining_installments'])->toBe(3);
});

it('rota exige autenticacao', function () {
    $this->get(route('installment-purchases.index'))
        ->assertRedirect(route('login'));
});

it('tela carrega para usuario autenticado', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('installment-purchases.index'))
        ->assertOk()
        ->assertSee('Compras parceladas');
});

it('filtro quitadas mostra apenas compras concluidas', function () {
    Carbon::setTestNow('2026-08-15');
    $user = User::factory()->create();
    $fixtures = installmentPurchaseFixtures();

    createInstallmentPurchase(
        $user,
        $fixtures,
        'Ativa',
        100.00,
        4,
        '2026-03-10',
        ['2026-04-10', '2026-05-10', '2026-06-10', '2026-09-10']
    );

    createInstallmentPurchase(
        $user,
        $fixtures,
        'Quitada',
        100.00,
        2,
        '2026-01-01',
        ['2026-02-10', '2026-03-10']
    );

    $data = app(InstallmentPurchaseService::class)->forUser($user, ['status' => 'completed']);

    expect($data['items'])->toHaveCount(1);
    expect($data['items'][0]['description'])->toBe('Quitada');
});

it('calcula resumo de compras ativas e valor restante', function () {
    Carbon::setTestNow('2026-06-15');
    $user = User::factory()->create();
    $fixtures = installmentPurchaseFixtures();

    createInstallmentPurchase(
        $user,
        $fixtures,
        'Notebook',
        250.00,
        4,
        '2026-03-10',
        ['2026-04-10', '2026-05-10', '2026-06-10', '2026-07-10']
    );

    createInstallmentPurchase(
        $user,
        $fixtures,
        'Celular',
        150.00,
        3,
        '2026-04-01',
        ['2026-05-10', '2026-06-10', '2026-07-10']
    );

    $summary = app(InstallmentPurchaseService::class)->forUser($user)['summary'];

    expect($summary['active_count'])->toBe(2);
    expect($summary['remaining_total'])->toBe(400.0);
});
