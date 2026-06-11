<?php

use App\Models\Category;
use App\Models\CreditCard;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Models\Type;
use App\Models\User;
use App\Services\PartnerSettlementService;
use Illuminate\Support\Facades\DB;

function createSettlementFixtures(): array
{
    $category = Category::create(['name' => 'Casa', 'slug' => 'casa-settle']);
    $expenseType = Type::create(['name' => 'Despesa', 'slug' => 'dc']);
    $paymentMethod = PaymentMethod::create(['name' => 'Pix', 'slug' => 'pix-settle']);

    return compact('category', 'expenseType', 'paymentMethod');
}

function linkPartners(User $userA, User $userB): void
{
    DB::table('user_relations')->insert([
        ['user_id' => $userA->id, 'related_user_id' => $userB->id, 'relation_type' => 'partner', 'created_at' => now(), 'updated_at' => now()],
        ['user_id' => $userB->id, 'related_user_id' => $userA->id, 'relation_type' => 'partner', 'created_at' => now(), 'updated_at' => now()],
    ]);
}

function createSharedExpense(
    User $payer,
    array $participantIds,
    float $amount,
    string $dueDate,
    array $fixtures,
    ?int $paidByUserId = null
): Transaction {
    ['category' => $category, 'expenseType' => $expenseType, 'paymentMethod' => $paymentMethod] = $fixtures;

    $transaction = Transaction::create([
        'description' => 'Gasto compartilhado',
        'amount' => $amount,
        'total_amount' => $amount,
        'transaction_date' => $dueDate,
        'due_date' => $dueDate,
        'category_id' => $category->id,
        'type_id' => $expenseType->id,
        'payment_method_id' => $paymentMethod->id,
        'paid_by_user_id' => $paidByUserId ?? $payer->id,
    ]);
    $transaction->users()->sync($participantIds);

    return $transaction;
}

it('usuario sem parceiro ve empty state na tela', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('partner-settlements.index'))
        ->assertOk()
        ->assertSee('Nenhum parceiro vinculado');
});

it('mes sem transacoes compartilhadas retorna total zero', function () {
    $userA = User::factory()->create(['name' => 'Daniel']);
    $userB = User::factory()->create(['name' => 'Joyce']);
    linkPartners($userA, $userB);

    $data = app(PartnerSettlementService::class)->forMonth(2026, 6, $userA);

    expect($data['total_shared'])->toBe(0.0);
    expect($data['transactions_count'])->toBe(0);
    expect($data['has_shared_expenses'])->toBeFalse();
});

it('transacao compartilhada entre 2 pessoas calcula 50/50', function () {
    $userA = User::factory()->create(['name' => 'Daniel']);
    $userB = User::factory()->create(['name' => 'Joyce']);
    linkPartners($userA, $userB);
    $fixtures = createSettlementFixtures();

    createSharedExpense($userA, [$userA->id, $userB->id], 200.00, '2026-06-10', $fixtures, $userA->id);

    $data = app(PartnerSettlementService::class)->forMonth(2026, 6, $userA);
    $byName = collect($data['participants'])->keyBy('name');

    expect($byName['Daniel']['share'])->toBe(100.0);
    expect($byName['Joyce']['share'])->toBe(100.0);
});

it('pessoa que pagou mais fica com valor a receber', function () {
    $userA = User::factory()->create(['name' => 'Daniel']);
    $userB = User::factory()->create(['name' => 'Joyce']);
    linkPartners($userA, $userB);
    $fixtures = createSettlementFixtures();

    createSharedExpense($userA, [$userA->id, $userB->id], 1200.00, '2026-06-05', $fixtures, $userA->id);
    createSharedExpense($userB, [$userA->id, $userB->id], 800.00, '2026-06-12', $fixtures, $userB->id);

    $data = app(PartnerSettlementService::class)->forMonth(2026, 6, $userA);
    $byName = collect($data['participants'])->keyBy('name');

    expect($data['total_shared'])->toBe(2000.0);
    expect($byName['Daniel']['paid'])->toBe(1200.0);
    expect($byName['Daniel']['share'])->toBe(1000.0);
    expect($byName['Daniel']['balance'])->toBe(200.0);
    expect($byName['Daniel']['status'])->toBe('receives');
});

it('pessoa que pagou menos fica com valor a pagar', function () {
    $userA = User::factory()->create(['name' => 'Daniel']);
    $userB = User::factory()->create(['name' => 'Joyce']);
    linkPartners($userA, $userB);
    $fixtures = createSettlementFixtures();

    createSharedExpense($userA, [$userA->id, $userB->id], 1200.00, '2026-06-05', $fixtures, $userA->id);
    createSharedExpense($userB, [$userA->id, $userB->id], 800.00, '2026-06-12', $fixtures, $userB->id);

    $data = app(PartnerSettlementService::class)->forMonth(2026, 6, $userA);
    $joyce = collect($data['participants'])->firstWhere('name', 'Joyce');

    expect($joyce['paid'])->toBe(800.0);
    expect($joyce['share'])->toBe(1000.0);
    expect($joyce['balance'])->toBe(-200.0);
    expect($joyce['status'])->toBe('owes');
});

it('sugestao de acerto e gerada corretamente', function () {
    $userA = User::factory()->create(['name' => 'Daniel']);
    $userB = User::factory()->create(['name' => 'Joyce']);
    linkPartners($userA, $userB);
    $fixtures = createSettlementFixtures();

    createSharedExpense($userA, [$userA->id, $userB->id], 1200.00, '2026-06-05', $fixtures, $userA->id);
    createSharedExpense($userB, [$userA->id, $userB->id], 800.00, '2026-06-12', $fixtures, $userB->id);

    $data = app(PartnerSettlementService::class)->forMonth(2026, 6, $userA);

    expect($data['suggestions'])->toHaveCount(1);
    expect($data['suggestions'][0]['from'])->toBe('Joyce');
    expect($data['suggestions'][0]['to'])->toBe('Daniel');
    expect($data['suggestions'][0]['amount'])->toBe(200.0);
    expect($data['suggestions'][0]['message'])->toContain('Joyce deve pagar R$ 200,00 para Daniel.');
});

it('transacoes individuais nao entram no calculo', function () {
    $userA = User::factory()->create(['name' => 'Daniel']);
    $userB = User::factory()->create(['name' => 'Joyce']);
    linkPartners($userA, $userB);
    $fixtures = createSettlementFixtures();

    createSharedExpense($userA, [$userA->id], 500.00, '2026-06-10', $fixtures, $userA->id);

    $data = app(PartnerSettlementService::class)->forMonth(2026, 6, $userA);

    expect($data['total_shared'])->toBe(0.0);
    expect($data['transactions_count'])->toBe(0);
});

it('transacoes de outro usuario fora da rede nao entram', function () {
    $userA = User::factory()->create(['name' => 'Daniel']);
    $userB = User::factory()->create(['name' => 'Joyce']);
    $userC = User::factory()->create(['name' => 'Carlos']);
    linkPartners($userA, $userB);
    $fixtures = createSettlementFixtures();

    createSharedExpense($userC, [$userB->id, $userC->id], 900.00, '2026-06-10', $fixtures, $userC->id);

    $data = app(PartnerSettlementService::class)->forMonth(2026, 6, $userA);

    expect($data['total_shared'])->toBe(0.0);
});

it('mes selecionado e respeitado', function () {
    $userA = User::factory()->create(['name' => 'Daniel']);
    $userB = User::factory()->create(['name' => 'Joyce']);
    linkPartners($userA, $userB);
    $fixtures = createSettlementFixtures();

    createSharedExpense($userA, [$userA->id, $userB->id], 300.00, '2026-06-10', $fixtures, $userA->id);
    createSharedExpense($userA, [$userA->id, $userB->id], 400.00, '2026-07-10', $fixtures, $userA->id);

    $june = app(PartnerSettlementService::class)->forMonth(2026, 6, $userA);
    $july = app(PartnerSettlementService::class)->forMonth(2026, 7, $userA);

    expect($june['total_shared'])->toBe(300.0);
    expect($july['total_shared'])->toBe(400.0);
});

it('varias transacoes no mes sao somadas corretamente', function () {
    $userA = User::factory()->create(['name' => 'Daniel']);
    $userB = User::factory()->create(['name' => 'Joyce']);
    linkPartners($userA, $userB);
    $fixtures = createSettlementFixtures();

    createSharedExpense($userA, [$userA->id, $userB->id], 150.00, '2026-06-01', $fixtures, $userA->id);
    createSharedExpense($userA, [$userA->id, $userB->id], 250.00, '2026-06-15', $fixtures, $userA->id);
    createSharedExpense($userB, [$userA->id, $userB->id], 100.00, '2026-06-20', $fixtures, $userB->id);

    $data = app(PartnerSettlementService::class)->forMonth(2026, 6, $userA);

    expect($data['total_shared'])->toBe(500.0);
    expect($data['transactions_count'])->toBe(3);
});

it('valores com centavos arredondam corretamente', function () {
    $userA = User::factory()->create(['name' => 'Ana']);
    $userB = User::factory()->create(['name' => 'Bruno']);
    $userC = User::factory()->create(['name' => 'Carla']);
    linkPartners($userA, $userB);
    linkPartners($userA, $userC);
    $fixtures = createSettlementFixtures();

    createSharedExpense($userA, [$userA->id, $userB->id, $userC->id], 100.00, '2026-06-10', $fixtures, $userA->id);

    $data = app(PartnerSettlementService::class)->forMonth(2026, 6, $userA);
    $shares = collect($data['participants'])->pluck('share')->all();

    expect(array_sum($shares))->toBe(100.0);
    expect($shares)->toContain(33.34);
});

it('rota exige autenticacao', function () {
    $this->get(route('partner-settlements.index'))
        ->assertRedirect(route('login'));
});

it('tela de acerto mensal carrega para usuario autenticado', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('partner-settlements.index'))
        ->assertOk()
        ->assertSee('Acerto mensal');
});

it('usa dono do cartao como pagador em compras no cartao', function () {
    $userA = User::factory()->create(['name' => 'Daniel']);
    $userB = User::factory()->create(['name' => 'Joyce']);
    linkPartners($userA, $userB);
    $fixtures = createSettlementFixtures();

    $card = CreditCard::create([
        'name' => 'Visa',
        'closing_day' => 5,
        'due_day' => 12,
        'owner_user_id' => $userA->id,
        'is_shared' => true,
    ]);
    $card->users()->sync([$userA->id, $userB->id]);

    $transaction = Transaction::create([
        'description' => 'Supermercado',
        'amount' => 300.00,
        'total_amount' => 300.00,
        'transaction_date' => '2026-06-01',
        'due_date' => '2026-06-12',
        'category_id' => $fixtures['category']->id,
        'type_id' => $fixtures['expenseType']->id,
        'payment_method_id' => $fixtures['paymentMethod']->id,
        'credit_card_id' => $card->id,
    ]);
    $transaction->users()->sync([$userA->id, $userB->id]);

    $data = app(PartnerSettlementService::class)->forMonth(2026, 6, $userA);
    $daniel = collect($data['participants'])->firstWhere('name', 'Daniel');

    expect($daniel['paid'])->toBe(300.0);
    expect($daniel['share'])->toBe(150.0);
    expect($daniel['balance'])->toBe(150.0);
});

it('grava paid_by_user_id ao criar transacao via repository', function () {
    $user = User::factory()->create();
    $fixtures = createSettlementFixtures();

    $this->actingAs($user);

    $transaction = app(\App\Repositories\TransactionRepository::class)->createTransaction([
        'description' => 'Teste',
        'amount' => 50,
        'total_amount' => 50,
        'transaction_date' => '2026-06-01',
        'due_date' => '2026-06-01',
        'category_id' => $fixtures['category']->id,
        'type_id' => $fixtures['expenseType']->id,
        'payment_method_id' => $fixtures['paymentMethod']->id,
    ], [$user->id]);

    expect($transaction->paid_by_user_id)->toBe($user->id);
});
