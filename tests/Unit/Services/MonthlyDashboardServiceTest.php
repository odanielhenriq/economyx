<?php

use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Models\TransactionInstallment;
use App\Models\Type;
use App\Models\User;
use App\Services\MonthlyDashboardService;
use Carbon\Carbon;

it('calcula corretamente a receita total do mês', function () {
    $user     = User::factory()->create();
    $category = Category::create(['name' => 'Salário', 'slug' => 'sal']);
    $type     = Type::create(['name' => 'Receita', 'slug' => 'rc']);
    $pm       = PaymentMethod::create(['name' => 'PIX', 'slug' => 'pix']);

    $tx = Transaction::create([
        'description'      => 'Salário',
        'total_amount'     => 5000.00,
        'amount'           => 5000.00,
        'transaction_date' => '2025-03-01',
        'due_date'         => '2025-03-05',
        'category_id'      => $category->id,
        'type_id'          => $type->id,
        'payment_method_id'=> $pm->id,
    ]);
    $tx->users()->sync([$user->id]);

    $data = app(MonthlyDashboardService::class)->build(2025, 3, $user);

    expect($data['cards']['income_total_month'])->toBe(5000.0);
});

it('calcula corretamente a despesa direta do mês', function () {
    $user     = User::factory()->create();
    $category = Category::create(['name' => 'Alimentação', 'slug' => 'alim']);
    $type     = Type::create(['name' => 'Despesa', 'slug' => 'dc']);
    $pm       = PaymentMethod::create(['name' => 'Débito', 'slug' => 'deb']);

    $tx = Transaction::create([
        'description'      => 'Supermercado',
        'total_amount'     => 300.00,
        'amount'           => 300.00,
        'transaction_date' => '2025-04-10',
        'due_date'         => '2025-04-10',
        'category_id'      => $category->id,
        'type_id'          => $type->id,
        'payment_method_id'=> $pm->id,
        'credit_card_id'   => null,
    ]);
    $tx->users()->sync([$user->id]);

    $data = app(MonthlyDashboardService::class)->build(2025, 4, $user);

    // directExpenseTotal deve capturar a transação à vista
    expect($data['cards']['expense_total_month'])->toBeGreaterThanOrEqual(300.0);
});

it('transações de outros usuários não aparecem nos totais', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $category = Category::create(['name' => 'Outros', 'slug' => 'outr']);
    $type     = Type::create(['name' => 'Receita', 'slug' => 'rc2']);
    $pm       = PaymentMethod::create(['name' => 'Dinheiro', 'slug' => 'din2']);

    // Transação do userB — não deve aparecer nos dados de userA
    $txB = Transaction::create([
        'description'      => 'Receita B',
        'total_amount'     => 9999.00,
        'amount'           => 9999.00,
        'transaction_date' => '2025-05-01',
        'due_date'         => '2025-05-01',
        'category_id'      => $category->id,
        'type_id'          => $type->id,
        'payment_method_id'=> $pm->id,
    ]);
    $txB->users()->sync([$userB->id]);

    $data = app(MonthlyDashboardService::class)->build(2025, 5, $userA);

    expect($data['cards']['income_total_month'])->toBe(0.0);
});

it('transações com soft delete não aparecem nos totais', function () {
    $user     = User::factory()->create();
    $category = Category::create(['name' => 'Deletados', 'slug' => 'del']);
    $type     = Type::create(['name' => 'Receita', 'slug' => 'rc3']);
    $pm       = PaymentMethod::create(['name' => 'Dinheiro', 'slug' => 'din3']);

    $tx = Transaction::create([
        'description'      => 'Receita deletada',
        'total_amount'     => 1000.00,
        'amount'           => 1000.00,
        'transaction_date' => '2025-06-01',
        'due_date'         => '2025-06-01',
        'category_id'      => $category->id,
        'type_id'          => $type->id,
        'payment_method_id'=> $pm->id,
    ]);
    $tx->users()->sync([$user->id]);
    $tx->delete(); // soft delete

    $data = app(MonthlyDashboardService::class)->build(2025, 6, $user);

    expect($data['cards']['income_total_month'])->toBe(0.0);
});

it('loanFallbackForMonth retorna a data correta para installment_number > 1', function () {
    $user     = User::factory()->create();
    $category = Category::create(['name' => 'Empréstimos', 'slug' => 'ep']);
    $type     = Type::create(['name' => 'Despesa', 'slug' => 'dc4']);
    $pm       = PaymentMethod::create(['name' => 'Transferência', 'slug' => 'tb']);

    // Empréstimo de 12x que começou em jan/2025, parcela 1 em fev/2025 (first_due_date)
    // Parcela atual: installment_number = 6 → vence em jul/2025
    $loan = Transaction::create([
        'description'       => 'Empréstimo',
        'total_amount'      => 1200.00,
        'amount'            => 100.00,
        'transaction_date'  => '2025-01-10',
        'due_date'          => '2025-01-10',
        'first_due_date'    => '2025-02-10',
        'installment_number'=> 6,
        'installment_total' => 12,
        'category_id'       => $category->id,
        'type_id'           => $type->id,
        'payment_method_id' => $pm->id,
        'credit_card_id'    => null,
    ]);
    $loan->users()->sync([$user->id]);

    // Parcela 6 → vence em jul/2025 (fev + 5 meses)
    $data = app(MonthlyDashboardService::class)->build(2025, 7, $user);

    expect(count($data['lists']['payables_loans']))->toBe(1);
    expect($data['lists']['payables_loans'][0]['installment_number'])->toBe(6);
    expect($data['lists']['payables_loans'][0]['due_date'])->toContain('2025-07');
});
