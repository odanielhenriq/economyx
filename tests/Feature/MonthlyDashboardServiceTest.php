<?php

use App\Models\Category;
use App\Models\CreditCard;
use App\Models\CreditCardStatement;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Models\TransactionInstallment;
use App\Models\Type;
use App\Models\User;
use App\Services\MonthlyDashboardService;
use Carbon\Carbon;

it('calcula despesas com faturas e emprestimos sem duplicar transacoes pai', function () {
    $user = User::factory()->create();

    $category = Category::create(['name' => 'Gerais', 'slug' => 'gerais']);
    $expenseType = Type::create(['name' => 'Despesa', 'slug' => 'dc']);
    $incomeType = Type::create(['name' => 'Receita', 'slug' => 'rc']);
    $paymentMethod = PaymentMethod::create(['name' => 'Dinheiro', 'slug' => 'cash']);

    $year = 2025;
    $month = 11;
    $baseDate = Carbon::create($year, $month, 10)->toDateString();

    $directExpense = Transaction::create([
        'description' => 'Mercado',
        'amount' => 100.00,
        'total_amount' => 100.00,
        'transaction_date' => $baseDate,
        'due_date' => $baseDate,
        'category_id' => $category->id,
        'type_id' => $expenseType->id,
        'payment_method_id' => $paymentMethod->id,
        'credit_card_id' => null,
    ]);
    $directExpense->users()->sync([$user->id]);

    $income = Transaction::create([
        'description' => 'Salario',
        'amount' => 500.00,
        'total_amount' => 500.00,
        'transaction_date' => $baseDate,
        'due_date' => $baseDate,
        'category_id' => $category->id,
        'type_id' => $incomeType->id,
        'payment_method_id' => $paymentMethod->id,
        'credit_card_id' => null,
    ]);
    $income->users()->sync([$user->id]);

    $card = CreditCard::create([
        'name' => 'Nubank',
        'closing_day' => 5,
        'due_day' => 15,
        'owner_user_id' => $user->id,
        'is_shared' => true,
    ]);
    $card->users()->sync([$user->id]);

    $cardTransaction = Transaction::create([
        'description' => 'Compra no cartao',
        'amount' => 200.00,
        'total_amount' => 200.00,
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
        'due_day' => 15,
        'status' => 'open',
    ]);

    TransactionInstallment::create([
        'transaction_id' => $cardTransaction->id,
        'credit_card_statement_id' => $statement->id,
        'installment_number' => 1,
        'installment_total' => 1,
        'amount' => 200.00,
        'year' => $year,
        'month' => $month,
        'due_date' => Carbon::create($year, $month, 15)->toDateString(),
    ]);

    $loanTransaction = Transaction::create([
        'description' => 'Financiamento',
        'amount' => 300.00,
        'total_amount' => 300.00,
        'transaction_date' => $baseDate,
        'due_date' => $baseDate,
        'category_id' => $category->id,
        'type_id' => $expenseType->id,
        'payment_method_id' => $paymentMethod->id,
        'credit_card_id' => null,
    ]);
    $loanTransaction->users()->sync([$user->id]);

    TransactionInstallment::create([
        'transaction_id' => $loanTransaction->id,
        'credit_card_statement_id' => null,
        'installment_number' => 1,
        'installment_total' => 3,
        'amount' => 120.00,
        'year' => $year,
        'month' => $month,
        'due_date' => Carbon::create($year, $month, 20)->toDateString(),
    ]);

    $service = new MonthlyDashboardService();
    $data = $service->build($year, $month, $user);

    expect($data['cards']['income_total_month'])->toBe(500.0);
    expect($data['cards']['payable_total_month'])->toBe(320.0);
    expect($data['cards']['expense_total_month'])->toBe(420.0);
    expect($data['cards']['balance_month'])->toBe(80.0);

    expect(count($data['lists']['payables_cards']))->toBe(1);
    expect(count($data['lists']['payables_loans']))->toBe(1);
});

it('inclui emprestimo sem parcelas usando fallback', function () {
    $user = User::factory()->create();

    $category = Category::create(['name' => 'Empréstimos', 'slug' => 'ep']);
    $expenseType = Type::create(['name' => 'Despesa', 'slug' => 'dc']);
    $paymentMethod = PaymentMethod::create(['name' => 'Transferência', 'slug' => 'tb']);

    $year = 2025;
    $month = 12;

    $loan = Transaction::create([
        'description' => 'Empréstimo pessoal',
        'amount' => 300.00,
        'total_amount' => 300.00,
        'transaction_date' => Carbon::create($year, 11, 5)->toDateString(),
        'due_date' => Carbon::create($year, 11, 5)->toDateString(),
        'first_due_date' => Carbon::create($year, $month, 10)->toDateString(),
        'installment_total' => 3,
        'category_id' => $category->id,
        'type_id' => $expenseType->id,
        'payment_method_id' => $paymentMethod->id,
        'credit_card_id' => null,
    ]);
    $loan->users()->sync([$user->id]);

    $service = new MonthlyDashboardService();
    $data = $service->build($year, $month, $user);

    expect($data['cards']['payable_loans_total'])->toBe(300.0);
    expect(count($data['lists']['payables_loans']))->toBe(1);
    expect($data['lists']['payables_loans'][0]['description'])->toBe('Empréstimo pessoal');
});
