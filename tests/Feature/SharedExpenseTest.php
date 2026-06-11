<?php

use App\Models\Category;
use App\Models\CreditCard;
use App\Models\CreditCardStatement;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Models\Type;
use App\Models\User;
use App\Services\SharedExpenseService;
use App\Services\SharedExpenseSettlementService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

function sharedExpenseFixtures(): array
{
    $category = Category::create(['name' => 'Casa', 'slug' => 'casa-shared']);
    $expenseType = Type::create(['name' => 'Despesa', 'slug' => 'dc']);
    $paymentMethod = PaymentMethod::create(['name' => 'Pix', 'slug' => 'pix-shared']);

    return compact('category', 'expenseType', 'paymentMethod');
}

function linkSharedPartners(User $userA, User $userB): void
{
    DB::table('user_relations')->insert([
        ['user_id' => $userA->id, 'related_user_id' => $userB->id, 'relation_type' => 'partner', 'created_at' => now(), 'updated_at' => now()],
        ['user_id' => $userB->id, 'related_user_id' => $userA->id, 'relation_type' => 'partner', 'created_at' => now(), 'updated_at' => now()],
    ]);
}

function createSharedTransaction(
    array $participantIds,
    float $amount,
    string $dueDate,
    array $fixtures,
    ?int $paidByUserId = null,
    ?string $description = null
): Transaction {
    ['category' => $category, 'expenseType' => $expenseType, 'paymentMethod' => $paymentMethod] = $fixtures;

    $transaction = Transaction::create([
        'description' => $description ?? 'Gasto compartilhado',
        'amount' => $amount,
        'total_amount' => $amount,
        'transaction_date' => $dueDate,
        'due_date' => $dueDate,
        'category_id' => $category->id,
        'type_id' => $expenseType->id,
        'payment_method_id' => $paymentMethod->id,
        'paid_by_user_id' => $paidByUserId ?? $participantIds[0],
    ]);
    $transaction->users()->sync($participantIds);

    return $transaction;
}

it('exibe transacao compartilhada em gastos compartilhados', function () {
    $userA = User::factory()->create(['name' => 'Daniel']);
    $userB = User::factory()->create(['name' => 'Joyce']);
    linkSharedPartners($userA, $userB);
    $fixtures = sharedExpenseFixtures();

    createSharedTransaction([$userA->id, $userB->id], 200.00, '2026-06-10', $fixtures, $userA->id, 'Mercado');

    $data = app(SharedExpenseService::class)->forMonth(2026, 6, $userA);

    expect($data['has_shared_expenses'])->toBeTrue();
    expect($data['expenses'])->toHaveCount(1);
    expect($data['expenses'][0]['description'])->toBe('Mercado');
});

it('nao exibe transacao individual em gastos compartilhados', function () {
    $userA = User::factory()->create(['name' => 'Daniel']);
    $userB = User::factory()->create(['name' => 'Joyce']);
    linkSharedPartners($userA, $userB);
    $fixtures = sharedExpenseFixtures();

    createSharedTransaction([$userA->id], 500.00, '2026-06-10', $fixtures, $userA->id);

    $data = app(SharedExpenseService::class)->forMonth(2026, 6, $userA);

    expect($data['has_shared_expenses'])->toBeFalse();
    expect($data['expenses'])->toBeEmpty();
});

it('calcula parte individual em transacao com 2 participantes', function () {
    $userA = User::factory()->create(['name' => 'Daniel']);
    $userB = User::factory()->create(['name' => 'Joyce']);
    linkSharedPartners($userA, $userB);
    $fixtures = sharedExpenseFixtures();

    createSharedTransaction([$userA->id, $userB->id], 200.00, '2026-06-10', $fixtures, $userA->id);

    $expense = app(SharedExpenseService::class)->forMonth(2026, 6, $userA)['expenses'][0];
    $shares = collect($expense['participants'])->pluck('share', 'name');

    expect($shares['Daniel'])->toBe(100.0);
    expect($shares['Joyce'])->toBe(100.0);
});

it('calcula parte individual em transacao com 3 participantes', function () {
    $userA = User::factory()->create(['name' => 'Ana']);
    $userB = User::factory()->create(['name' => 'Bruno']);
    $userC = User::factory()->create(['name' => 'Carla']);
    linkSharedPartners($userA, $userB);
    linkSharedPartners($userA, $userC);
    $fixtures = sharedExpenseFixtures();

    createSharedTransaction([$userA->id, $userB->id, $userC->id], 100.00, '2026-06-10', $fixtures, $userA->id);

    $shares = collect(app(SharedExpenseService::class)->forMonth(2026, 6, $userA)['expenses'][0]['participants'])
        ->pluck('share')
        ->all();

    expect(array_sum($shares))->toBe(100.0);
});

it('permite marcar minha parte como acertada', function () {
    $userA = User::factory()->create(['name' => 'Daniel']);
    $userB = User::factory()->create(['name' => 'Joyce']);
    linkSharedPartners($userA, $userB);
    $fixtures = sharedExpenseFixtures();

    $transaction = createSharedTransaction([$userA->id, $userB->id], 200.00, '2026-06-10', $fixtures, $userA->id);

    app(SharedExpenseSettlementService::class)->settle($transaction, $userB, $userB);

    $pivot = DB::table('transaction_user')
        ->where('transaction_id', $transaction->id)
        ->where('user_id', $userB->id)
        ->first();

    expect((bool) $pivot->is_settled)->toBeTrue();
    expect((int) $pivot->settled_to_user_id)->toBe($userA->id);
});

it('permite pagador marcar parte do parceiro como acertada', function () {
    $userA = User::factory()->create(['name' => 'Daniel']);
    $userB = User::factory()->create(['name' => 'Joyce']);
    linkSharedPartners($userA, $userB);
    $fixtures = sharedExpenseFixtures();

    $transaction = createSharedTransaction([$userA->id, $userB->id], 200.00, '2026-06-10', $fixtures, $userA->id);

    app(SharedExpenseSettlementService::class)->settle($transaction, $userB, $userA);

    expect((bool) DB::table('transaction_user')
        ->where('transaction_id', $transaction->id)
        ->where('user_id', $userB->id)
        ->value('is_settled'))->toBeTrue();
});

it('permite desfazer acerto', function () {
    $userA = User::factory()->create(['name' => 'Daniel']);
    $userB = User::factory()->create(['name' => 'Joyce']);
    linkSharedPartners($userA, $userB);
    $fixtures = sharedExpenseFixtures();

    $transaction = createSharedTransaction([$userA->id, $userB->id], 200.00, '2026-06-10', $fixtures, $userA->id);
    $service = app(SharedExpenseSettlementService::class);

    $service->settle($transaction, $userB, $userB);
    $service->unsettle($transaction, $userB, $userB);

    expect((bool) DB::table('transaction_user')
        ->where('transaction_id', $transaction->id)
        ->where('user_id', $userB->id)
        ->value('is_settled'))->toBeFalse();
});

it('parte acertada deixa de aparecer como pendente', function () {
    $userA = User::factory()->create(['name' => 'Daniel']);
    $userB = User::factory()->create(['name' => 'Joyce']);
    linkSharedPartners($userA, $userB);
    $fixtures = sharedExpenseFixtures();

    $transaction = createSharedTransaction([$userA->id, $userB->id], 200.00, '2026-06-10', $fixtures, $userA->id);
    app(SharedExpenseSettlementService::class)->settle($transaction, $userB, $userB);

    $data = app(SharedExpenseService::class)->forMonth(2026, 6, $userA);

    expect($data['summary']['pending_settlement'])->toBe(0.0);
    expect($data['summary']['settled_total'])->toBe(100.0);
    expect($data['suggestions'])->toBeEmpty();
});

it('parte acertada aparece como acertada', function () {
    $userA = User::factory()->create(['name' => 'Daniel']);
    $userB = User::factory()->create(['name' => 'Joyce']);
    linkSharedPartners($userA, $userB);
    $fixtures = sharedExpenseFixtures();

    $transaction = createSharedTransaction([$userA->id, $userB->id], 200.00, '2026-06-10', $fixtures, $userA->id);
    app(SharedExpenseSettlementService::class)->settle($transaction, $userB, $userB);

    $joyce = collect(app(SharedExpenseService::class)->forMonth(2026, 6, $userA)['expenses'][0]['participants'])
        ->firstWhere('name', 'Joyce');

    expect($joyce['settlement_status'])->toBe('settled');
    expect($joyce['is_settled'])->toBeTrue();
});

it('fatura paga ao banco nao marca automaticamente acerto do parceiro', function () {
    $userA = User::factory()->create(['name' => 'Daniel']);
    $userB = User::factory()->create(['name' => 'Joyce']);
    linkSharedPartners($userA, $userB);
    $fixtures = sharedExpenseFixtures();

    $card = CreditCard::create([
        'name' => 'Visa',
        'closing_day' => 5,
        'due_day' => 12,
        'owner_user_id' => $userA->id,
        'is_shared' => true,
    ]);
    $card->users()->sync([$userA->id, $userB->id]);

    CreditCardStatement::create([
        'credit_card_id' => $card->id,
        'year' => 2026,
        'month' => 6,
        'period_start' => '2026-05-06',
        'period_end' => '2026-06-05',
        'closing_day' => 5,
        'due_day' => 12,
        'status' => 'paid',
    ]);

    $transaction = Transaction::create([
        'description' => 'Restaurante',
        'amount' => 300.00,
        'total_amount' => 300.00,
        'transaction_date' => '2026-06-01',
        'due_date' => '2026-06-12',
        'category_id' => $fixtures['category']->id,
        'type_id' => $fixtures['expenseType']->id,
        'payment_method_id' => $fixtures['paymentMethod']->id,
        'credit_card_id' => $card->id,
        'paid_by_user_id' => $userA->id,
    ]);
    $transaction->users()->sync([$userA->id, $userB->id]);

    $data = app(SharedExpenseService::class)->forMonth(2026, 6, $userA);

    expect($data['summary']['pending_settlement'])->toBe(150.0);
    expect((bool) DB::table('transaction_user')->where('user_id', $userB->id)->value('is_settled'))->toBeFalse();
});

it('usuario fora da rede nao consegue marcar acerto', function () {
    $userA = User::factory()->create(['name' => 'Daniel']);
    $userB = User::factory()->create(['name' => 'Joyce']);
    $userC = User::factory()->create(['name' => 'Carlos']);
    linkSharedPartners($userA, $userB);
    $fixtures = sharedExpenseFixtures();

    $transaction = createSharedTransaction([$userA->id, $userB->id], 200.00, '2026-06-10', $fixtures, $userA->id);

    app(SharedExpenseSettlementService::class)->settle($transaction, $userB, $userC);
})->throws(Illuminate\Auth\Access\AuthorizationException::class);

it('transacoes fora do mes selecionado nao entram', function () {
    $userA = User::factory()->create(['name' => 'Daniel']);
    $userB = User::factory()->create(['name' => 'Joyce']);
    linkSharedPartners($userA, $userB);
    $fixtures = sharedExpenseFixtures();

    createSharedTransaction([$userA->id, $userB->id], 200.00, '2026-06-10', $fixtures, $userA->id);
    createSharedTransaction([$userA->id, $userB->id], 300.00, '2026-07-10', $fixtures, $userA->id);

    $june = app(SharedExpenseService::class)->forMonth(2026, 6, $userA);

    expect($june['expenses'])->toHaveCount(1);
    expect($june['summary']['total_shared'])->toBe(200.0);
});

it('transacoes antigas sem campos novos continuam pendentes', function () {
    $userA = User::factory()->create(['name' => 'Daniel']);
    $userB = User::factory()->create(['name' => 'Joyce']);
    linkSharedPartners($userA, $userB);
    $fixtures = sharedExpenseFixtures();

    createSharedTransaction([$userA->id, $userB->id], 120.00, '2026-06-10', $fixtures, $userA->id);

    $joyce = collect(app(SharedExpenseService::class)->forMonth(2026, 6, $userA)['expenses'][0]['participants'])
        ->firstWhere('name', 'Joyce');

    expect($joyce['settlement_status'])->toBe('pending');
    expect($joyce['is_settled'])->toBeFalse();
});

it('usa pivot transaction_user para acerto', function () {
    $userA = User::factory()->create(['name' => 'Daniel']);
    $userB = User::factory()->create(['name' => 'Joyce']);
    linkSharedPartners($userA, $userB);
    $fixtures = sharedExpenseFixtures();

    $transaction = createSharedTransaction([$userA->id, $userB->id], 200.00, '2026-06-10', $fixtures, $userA->id);

    $this->actingAs($userB)
        ->patchJson(route('shared-expenses.settle', [$transaction, $userB]))
        ->assertOk();

    expect(Schema::hasColumn('transaction_user', 'is_settled'))->toBeTrue();
});

it('rotas exigem autenticacao', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $fixtures = sharedExpenseFixtures();
    $transaction = createSharedTransaction([$userA->id, $userB->id], 100.00, '2026-06-01', $fixtures, $userA->id);

    $this->get(route('shared-expenses.index'))->assertRedirect(route('login'));
    $this->patch(route('shared-expenses.settle', [$transaction, $userB]))->assertRedirect(route('login'));
});

it('tela de gastos compartilhados carrega para usuario autenticado', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('shared-expenses.index'))
        ->assertOk()
        ->assertSee('Gastos compartilhados');
});

it('usuario sem parceiro ve empty state', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('shared-expenses.index'))
        ->assertOk()
        ->assertSee('Nenhum parceiro vinculado');
});

it('redireciona rota legada partner-settlements', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/partner-settlements')
        ->assertRedirect('/shared-expenses');
});

it('filtra gastos pendentes', function () {
    $userA = User::factory()->create(['name' => 'Daniel']);
    $userB = User::factory()->create(['name' => 'Joyce']);
    linkSharedPartners($userA, $userB);
    $fixtures = sharedExpenseFixtures();

    $pending = createSharedTransaction([$userA->id, $userB->id], 200.00, '2026-06-10', $fixtures, $userA->id, 'Pendente');
    $settledTx = createSharedTransaction([$userA->id, $userB->id], 100.00, '2026-06-12', $fixtures, $userA->id, 'Acertado');
    app(SharedExpenseSettlementService::class)->settle($settledTx, $userB, $userB);

    $pendingOnly = app(SharedExpenseService::class)->forMonth(2026, 6, $userA, null, 'pending');

    expect(collect($pendingOnly['expenses'])->pluck('description')->all())->toContain('Pendente');
    expect(collect($pendingOnly['expenses'])->pluck('description')->all())->not->toContain('Acertado');
});
