<?php

use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\RecurringTransaction;
use App\Models\Transaction;
use App\Models\Type;
use App\Services\CashflowService;
use Carbon\Carbon;

it('inclui projection quando nao existe transacao materializada', function () {
    $category = Category::create(['name' => 'Assinaturas', 'slug' => 'assinaturas']);
    $type = Type::create(['name' => 'Despesa', 'slug' => 'dc']);
    $paymentMethod = PaymentMethod::create(['name' => 'Boleto', 'slug' => 'bl']);

    $template = RecurringTransaction::create([
        'description' => 'Spotify',
        'amount' => 25.90,
        'total_amount' => 25.90,
        'frequency' => 'monthly',
        'day_of_month' => 10,
        'start_date' => Carbon::now()->startOfMonth(),
        'is_active' => true,
        'category_id' => $category->id,
        'type_id' => $type->id,
        'payment_method_id' => $paymentMethod->id,
        'credit_card_id' => null,
    ]);

    $service = new CashflowService();
    $items = $service->forMonth((int) now()->year, (int) now()->month, true);

    $projectionCount = collect($items)->where('source', 'projection')->count();

    expect($projectionCount)->toBe(1);
    expect(collect($items)->first()['recurring_transaction_id'])->toBe($template->id);
});

it('nao projeta quando a transacao ja existe', function () {
    $category = Category::create(['name' => 'Assinaturas', 'slug' => 'assinaturas']);
    $type = Type::create(['name' => 'Despesa', 'slug' => 'dc']);
    $paymentMethod = PaymentMethod::create(['name' => 'Boleto', 'slug' => 'bl']);

    $template = RecurringTransaction::create([
        'description' => 'Spotify',
        'amount' => 25.90,
        'total_amount' => 25.90,
        'frequency' => 'monthly',
        'day_of_month' => 10,
        'start_date' => Carbon::now()->startOfMonth(),
        'is_active' => true,
        'category_id' => $category->id,
        'type_id' => $type->id,
        'payment_method_id' => $paymentMethod->id,
        'credit_card_id' => null,
    ]);

    $dueDate = Carbon::now()->startOfMonth()->day(10);

    Transaction::create([
        'description' => $template->description,
        'amount' => $template->amount,
        'total_amount' => $template->total_amount,
        'transaction_date' => $dueDate->toDateString(),
        'due_date' => $dueDate->toDateString(),
        'category_id' => $category->id,
        'type_id' => $type->id,
        'payment_method_id' => $paymentMethod->id,
        'credit_card_id' => null,
        'recurring_transaction_id' => $template->id,
    ]);

    $service = new CashflowService();
    $items = $service->forMonth((int) now()->year, (int) now()->month, true);

    $projectionCount = collect($items)->where('source', 'projection')->count();
    $transactionCount = collect($items)->where('source', 'transaction')->count();

    expect($projectionCount)->toBe(0);
    expect($transactionCount)->toBe(1);
});
