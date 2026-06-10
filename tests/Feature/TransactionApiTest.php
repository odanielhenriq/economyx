<?php

use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Models\TransactionInstallment;
use App\Models\Type;
use App\Models\User;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

beforeEach(function () {
    $this->typeExpense = Type::firstOrCreate(['slug' => 'dc'], ['name' => 'Despesa']);
    $this->category = Category::firstOrCreate(['slug' => 'ot'], ['name' => 'Outros']);
    $this->paymentMethod = PaymentMethod::firstOrCreate(['slug' => 'cc'], ['name' => 'Cartão']);
});

it('returns unauthorized without authentication', function () {
    getJson('/api/transactions')->assertUnauthorized();
});

it('includes installment transactions when filtering by month', function () {
    $user = User::factory()->create();

    $transaction = Transaction::create([
        'description' => 'Compra parcelada',
        'total_amount' => 300,
        'amount' => 100,
        'transaction_date' => '2025-09-10',
        'due_date' => '2025-10-10',
        'installment_number' => 1,
        'installment_total' => 3,
        'category_id' => $this->category->id,
        'type_id' => $this->typeExpense->id,
        'payment_method_id' => $this->paymentMethod->id,
    ]);
    $transaction->users()->sync([$user->id]);

    TransactionInstallment::create([
        'transaction_id' => $transaction->id,
        'installment_number' => 2,
        'installment_total' => 3,
        'amount' => 100,
        'year' => 2026,
        'month' => 6,
        'due_date' => '2026-06-15',
    ]);

    actingAs($user)
        ->getJson('/api/transactions?month=2026-06')
        ->assertOk()
        ->assertJsonFragment(['description' => 'Compra parcelada'])
        ->assertJsonPath('data.0.date', '2026-06-15')
        ->assertJsonPath('data.0.amount', 100);
});

it('does not include installment transactions outside filtered month', function () {
    $user = User::factory()->create();

    $transaction = Transaction::create([
        'description' => 'Parcela futura',
        'total_amount' => 200,
        'amount' => 100,
        'transaction_date' => '2025-09-10',
        'due_date' => '2025-10-10',
        'installment_number' => 1,
        'installment_total' => 2,
        'category_id' => $this->category->id,
        'type_id' => $this->typeExpense->id,
        'payment_method_id' => $this->paymentMethod->id,
    ]);
    $transaction->users()->sync([$user->id]);

    TransactionInstallment::create([
        'transaction_id' => $transaction->id,
        'installment_number' => 2,
        'installment_total' => 2,
        'amount' => 100,
        'year' => 2026,
        'month' => 7,
        'due_date' => '2026-07-15',
    ]);

    actingAs($user)
        ->getJson('/api/transactions?month=2026-06')
        ->assertOk()
        ->assertJsonMissing(['description' => 'Parcela futura']);
});
