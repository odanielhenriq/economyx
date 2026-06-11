<?php

use App\Models\Category;
use App\Models\MonthlySavingsGoal;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Models\Type;
use App\Models\User;
use App\Services\MonthlyDashboardService;
use App\Services\MonthlySavingsGoalService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

function savingsGoalFixtures(): array
{
    $category = Category::create(['name' => 'Gerais SG', 'slug' => 'gerais-sg']);
    $expenseType = Type::create(['name' => 'Despesa SG', 'slug' => 'dc']);
    $incomeType = Type::create(['name' => 'Receita SG', 'slug' => 'rc']);
    $paymentMethod = PaymentMethod::create(['name' => 'Pix SG', 'slug' => 'pix-sg']);

    return compact('category', 'expenseType', 'incomeType', 'paymentMethod');
}

function seedProjectedBalance(User $user, int $year, int $month, float $income, float $expense): void
{
    ['category' => $category, 'expenseType' => $expenseType, 'incomeType' => $incomeType, 'paymentMethod' => $paymentMethod] = savingsGoalFixtures();
    $date = Carbon::create($year, $month, 10)->toDateString();

    $incomeTx = Transaction::create([
        'description' => 'Salario',
        'amount' => $income,
        'total_amount' => $income,
        'transaction_date' => $date,
        'due_date' => $date,
        'category_id' => $category->id,
        'type_id' => $incomeType->id,
        'payment_method_id' => $paymentMethod->id,
    ]);
    $incomeTx->users()->sync([$user->id]);

    if ($expense > 0) {
        $expenseTx = Transaction::create([
            'description' => 'Mercado',
            'amount' => $expense,
            'total_amount' => $expense,
            'transaction_date' => $date,
            'due_date' => $date,
            'category_id' => $category->id,
            'type_id' => $expenseType->id,
            'payment_method_id' => $paymentMethod->id,
        ]);
        $expenseTx->users()->sync([$user->id]);
    }
}

it('usuario sem meta recebe savings_goal exists false', function () {
    $user = User::factory()->create();

    $payload = app(MonthlyDashboardService::class)->build(2026, 6, $user);

    expect($payload['savings_goal']['exists'])->toBeFalse();
    expect($payload['savings_goal']['message'])->toContain('Defina quanto você quer guardar');
});

it('usuario cria meta para mes especifico', function () {
    $user = User::factory()->create();

    $goal = app(MonthlySavingsGoalService::class)->upsert($user, 2026, 6, 500.00, 'Reserva');

    expect($goal->user_id)->toBe($user->id);
    expect($goal->year)->toBe(2026);
    expect($goal->month)->toBe(6);
    expect((float) $goal->target_amount)->toBe(500.0);
    expect($goal->note)->toBe('Reserva');
});

it('usuario atualiza meta do mesmo mes', function () {
    $user = User::factory()->create();
    $service = app(MonthlySavingsGoalService::class);

    $service->upsert($user, 2026, 6, 500.00);
    $updated = $service->upsert($user, 2026, 6, 700.00, 'Ajuste');

    expect(MonthlySavingsGoal::count())->toBe(1);
    expect((float) $updated->target_amount)->toBe(700.0);
    expect($updated->note)->toBe('Ajuste');
});

it('nao permite meta negativa via rota', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->putJson(route('savings-goals.upsert'), [
            'year' => 2026,
            'month' => 6,
            'target_amount' => -100,
        ])
        ->assertUnprocessable();
});

it('nao permite meta de outro usuario ser editada indiretamente', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    app(MonthlySavingsGoalService::class)->upsert($owner, 2026, 6, 500.00);

    $this->actingAs($other)
        ->putJson(route('savings-goals.upsert'), [
            'year' => 2026,
            'month' => 6,
            'target_amount' => 900,
        ])
        ->assertOk();

    expect((float) MonthlySavingsGoal::where('user_id', $owner->id)->first()->target_amount)->toBe(500.0);
    expect((float) MonthlySavingsGoal::where('user_id', $other->id)->first()->target_amount)->toBe(900.0);
});

it('dashboard retorna meta do mes selecionado', function () {
    $user = User::factory()->create();
    seedProjectedBalance($user, 2026, 6, 3000, 400);

    app(MonthlySavingsGoalService::class)->upsert($user, 2026, 6, 500.00);

    $payload = app(MonthlyDashboardService::class)->build(2026, 6, $user);

    expect($payload['savings_goal']['exists'])->toBeTrue();
    expect($payload['savings_goal']['target_amount'])->toBe(500.0);
    expect($payload['savings_goal']['projected_balance'])->toBe(2600.0);
});

it('trocar mes retorna outra meta ou sem meta', function () {
    $user = User::factory()->create();
    $service = app(MonthlySavingsGoalService::class);

    $service->upsert($user, 2026, 6, 500.00);
    $service->upsert($user, 2026, 7, 800.00);

    $june = app(MonthlyDashboardService::class)->build(2026, 6, $user);
    $july = app(MonthlyDashboardService::class)->build(2026, 7, $user);
    $august = app(MonthlyDashboardService::class)->build(2026, 8, $user);

    expect($june['savings_goal']['target_amount'])->toBe(500.0);
    expect($july['savings_goal']['target_amount'])->toBe(800.0);
    expect($august['savings_goal']['exists'])->toBeFalse();
});

it('status on_track quando saldo projetado maior ou igual a meta', function () {
    $user = User::factory()->create();
    seedProjectedBalance($user, 2026, 6, 3000, 400);

    app(MonthlySavingsGoalService::class)->upsert($user, 2026, 6, 500.00);

    $goal = app(MonthlyDashboardService::class)->build(2026, 6, $user)['savings_goal'];

    expect($goal['status'])->toBe('on_track');
    expect($goal['status_label'])->toBe('No caminho certo');
});

it('status attention quando saldo projetado menor que meta', function () {
    $user = User::factory()->create();
    seedProjectedBalance($user, 2026, 6, 1000, 800);

    app(MonthlySavingsGoalService::class)->upsert($user, 2026, 6, 500.00);

    $goal = app(MonthlyDashboardService::class)->build(2026, 6, $user)['savings_goal'];

    expect($goal['status'])->toBe('attention');
    expect($goal['status_label'])->toBe('Atenção');
});

it('calcula diferenca corretamente', function () {
    $user = User::factory()->create();
    seedProjectedBalance($user, 2026, 6, 3000, 400);

    app(MonthlySavingsGoalService::class)->upsert($user, 2026, 6, 500.00);

    $goal = app(MonthlyDashboardService::class)->build(2026, 6, $user)['savings_goal'];

    expect($goal['difference'])->toBe(2100.0);
    expect($goal['message'])->toContain('R$ 2.100,00 acima da meta');
});

it('calcula progresso percentual corretamente', function () {
    $user = User::factory()->create();
    seedProjectedBalance($user, 2026, 6, 1000, 800);

    app(MonthlySavingsGoalService::class)->upsert($user, 2026, 6, 500.00);

    $goal = app(MonthlyDashboardService::class)->build(2026, 6, $user)['savings_goal'];

    expect($goal['progress_percent'])->toBe(40.0);
});

it('meta e individual por usuario como orcamentos', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    DB::table('user_relations')->insert([
        ['user_id' => $userA->id, 'related_user_id' => $userB->id, 'relation_type' => 'partner', 'created_at' => now(), 'updated_at' => now()],
        ['user_id' => $userB->id, 'related_user_id' => $userA->id, 'relation_type' => 'partner', 'created_at' => now(), 'updated_at' => now()],
    ]);

    app(MonthlySavingsGoalService::class)->upsert($userA, 2026, 6, 500.00);
    app(MonthlySavingsGoalService::class)->upsert($userB, 2026, 6, 900.00);

    $goalA = app(MonthlySavingsGoalService::class)->findForUser($userA, 2026, 6);
    $goalB = app(MonthlySavingsGoalService::class)->findForUser($userB, 2026, 6);

    expect((float) $goalA->target_amount)->toBe(500.0);
    expect((float) $goalB->target_amount)->toBe(900.0);
});

it('rota exige autenticacao', function () {
    $this->put(route('savings-goals.upsert'), [
        'year' => 2026,
        'month' => 6,
        'target_amount' => 500,
    ])->assertRedirect(route('login'));
});

it('api do dashboard expoe savings_goal', function () {
    $user = User::factory()->create();
    app(MonthlySavingsGoalService::class)->upsert($user, 2026, 6, 500.00);

    $this->actingAs($user)
        ->getJson('/api/dashboard/monthly?year=2026&month=6')
        ->assertOk()
        ->assertJsonPath('savings_goal.exists', true)
        ->assertJsonPath('savings_goal.target_amount', 500);
});

it('salvar meta via rota retorna json', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->putJson(route('savings-goals.upsert'), [
            'year' => 2026,
            'month' => 6,
            'target_amount' => 500,
            'note' => 'Reserva',
        ])
        ->assertOk()
        ->assertJson(['message' => 'Meta de economia salva.']);
});
