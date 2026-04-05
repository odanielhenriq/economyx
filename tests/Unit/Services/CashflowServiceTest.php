<?php

use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\RecurringTransaction;
use App\Models\Transaction;
use App\Models\Type;
use App\Models\User;
use App\Services\CashflowService;
use Carbon\Carbon;

it('retorna transações de receita e despesa agrupadas no mês', function () {
    $user     = User::factory()->create();
    $category = Category::create(['name' => 'Gerais', 'slug' => 'ger']);
    $income   = Type::create(['name' => 'Receita', 'slug' => 'rc']);
    $expense  = Type::create(['name' => 'Despesa', 'slug' => 'dc']);
    $pm       = PaymentMethod::create(['name' => 'Dinheiro', 'slug' => 'din']);

    $txIncome = Transaction::create([
        'description'      => 'Salário',
        'total_amount'     => 3000.00,
        'amount'           => 3000.00,
        'transaction_date' => '2025-08-05',
        'due_date'         => '2025-08-05',
        'category_id'      => $category->id,
        'type_id'          => $income->id,
        'payment_method_id'=> $pm->id,
    ]);
    $txIncome->users()->sync([$user->id]);

    $txExpense = Transaction::create([
        'description'      => 'Aluguel',
        'total_amount'     => 1000.00,
        'amount'           => 1000.00,
        'transaction_date' => '2025-08-10',
        'due_date'         => '2025-08-10',
        'category_id'      => $category->id,
        'type_id'          => $expense->id,
        'payment_method_id'=> $pm->id,
    ]);
    $txExpense->users()->sync([$user->id]);

    $items = app(CashflowService::class)->forMonth(2025, 8, false, $user);

    $sources = collect($items)->pluck('source')->unique()->values()->toArray();
    expect($sources)->toContain('transaction');

    $income  = collect($items)->where('type_slug', 'rc')->sum('amount');
    $expense = collect($items)->where('type_slug', 'dc')->sum('amount');
    expect((float) $income)->toBe(3000.0);
    expect((float) $expense)->toBe(1000.0);
});

it('saldo projetado é receita menos despesa nas movimentações do mês', function () {
    $user     = User::factory()->create();
    $category = Category::create(['name' => 'Geral', 'slug' => 'geral2']);
    $rc       = Type::create(['name' => 'Receita', 'slug' => 'rc2']);
    $dc       = Type::create(['name' => 'Despesa', 'slug' => 'dc2']);
    $pm       = PaymentMethod::create(['name' => 'PIX', 'slug' => 'pix2']);

    $r = Transaction::create([
        'description' => 'Freelance', 'total_amount' => 2000.00, 'amount' => 2000.00,
        'transaction_date' => '2025-09-01', 'due_date' => '2025-09-01',
        'category_id' => $category->id, 'type_id' => $rc->id, 'payment_method_id' => $pm->id,
    ]);
    $r->users()->sync([$user->id]);

    $d = Transaction::create([
        'description' => 'Internet', 'total_amount' => 100.00, 'amount' => 100.00,
        'transaction_date' => '2025-09-05', 'due_date' => '2025-09-05',
        'category_id' => $category->id, 'type_id' => $dc->id, 'payment_method_id' => $pm->id,
    ]);
    $d->users()->sync([$user->id]);

    $items = app(CashflowService::class)->forMonth(2025, 9, false, $user);
    $net = collect($items)->whereIn('type_slug', ['rc2'])->sum('amount')
         - collect($items)->whereIn('type_slug', ['dc2'])->sum('amount');

    expect((float) $net)->toBe(1900.0);
});

it('transações futuras aparecem como projection quando template está ativo', function () {
    $category = Category::create(['name' => 'Assinaturas', 'slug' => 'ass2']);
    $type     = Type::create(['name' => 'Despesa', 'slug' => 'dc3']);
    $pm       = PaymentMethod::create(['name' => 'Débito', 'slug' => 'deb2']);

    RecurringTransaction::create([
        'description'      => 'Netflix',
        'amount'           => 39.90,
        'total_amount'     => 39.90,
        'frequency'        => 'monthly',
        'day_of_month'     => 5,
        'start_date'       => Carbon::create(2025, 10, 1),
        'is_active'        => true,
        'category_id'      => $category->id,
        'type_id'          => $type->id,
        'payment_method_id'=> $pm->id,
    ]);

    $items = app(CashflowService::class)->forMonth(2025, 10, true);
    $projections = collect($items)->where('source', 'projection');

    expect($projections->count())->toBeGreaterThanOrEqual(1);
    expect($projections->first()['description'])->toBe('Netflix');
});

it('transações de outros usuários não contaminam o cashflow filtrado por usuário', function () {
    $userA    = User::factory()->create();
    $userB    = User::factory()->create();
    $category = Category::create(['name' => 'Outros', 'slug' => 'out2']);
    $type     = Type::create(['name' => 'Receita', 'slug' => 'rc4']);
    $pm       = PaymentMethod::create(['name' => 'Dinheiro', 'slug' => 'din4']);

    $txB = Transaction::create([
        'description' => 'Receita B', 'total_amount' => 5000.00, 'amount' => 5000.00,
        'transaction_date' => '2025-11-10', 'due_date' => '2025-11-10',
        'category_id' => $category->id, 'type_id' => $type->id, 'payment_method_id' => $pm->id,
    ]);
    $txB->users()->sync([$userB->id]);

    $items = app(CashflowService::class)->forMonth(2025, 11, false, $userA);

    expect(collect($items)->where('source', 'transaction')->count())->toBe(0);
});
