<?php

use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Models\Type;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

beforeEach(function () {
    $this->typeExpense = Type::firstOrCreate(['slug' => 'dc'], ['name' => 'Despesa']);
    $this->category = Category::firstOrCreate(['slug' => 'ot'], ['name' => 'Outros']);
    $this->paymentMethod = PaymentMethod::firstOrCreate(['slug' => 'px'], ['name' => 'Pix']);
});

it('user A cannot see transactions belonging only to user B', function () {
    $userA = User::factory()->create(['name' => 'User A']);
    $userB = User::factory()->create(['name' => 'User B']);

    $transaction = Transaction::create([
        'description' => 'Secret expense',
        'total_amount' => 100,
        'amount' => 100,
        'transaction_date' => now()->toDateString(),
        'due_date' => now()->toDateString(),
        'category_id' => $this->category->id,
        'type_id' => $this->typeExpense->id,
        'payment_method_id' => $this->paymentMethod->id,
    ]);
    $transaction->users()->sync([$userB->id]);

    actingAs($userA)
        ->getJson('/api/transactions')
        ->assertOk()
        ->assertJsonMissing(['description' => 'Secret expense']);
});

it('network partners can see shared transactions', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    DB::table('user_relations')->insert([
        ['user_id' => $userA->id, 'related_user_id' => $userB->id, 'relation_type' => 'partner', 'created_at' => now(), 'updated_at' => now()],
        ['user_id' => $userB->id, 'related_user_id' => $userA->id, 'relation_type' => 'partner', 'created_at' => now(), 'updated_at' => now()],
    ]);

    $transaction = Transaction::create([
        'description' => 'Shared grocery',
        'total_amount' => 50,
        'amount' => 50,
        'transaction_date' => now()->toDateString(),
        'due_date' => now()->toDateString(),
        'category_id' => $this->category->id,
        'type_id' => $this->typeExpense->id,
        'payment_method_id' => $this->paymentMethod->id,
    ]);
    $transaction->users()->sync([$userB->id]);

    actingAs($userA)
        ->getJson('/api/transactions')
        ->assertOk()
        ->assertJsonFragment(['description' => 'Shared grocery']);
});

it('users endpoint returns only network users', function () {
    $userA = User::factory()->create();
    User::factory()->create();

    actingAs($userA)
        ->getJson('/api/users')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('user A cannot access recurring template belonging only to user B', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $template = \App\Models\RecurringTransaction::create([
        'description' => 'Secret rent',
        'amount' => 1500,
        'total_amount' => 1500,
        'frequency' => 'monthly',
        'day_of_month' => 5,
        'is_active' => true,
        'category_id' => $this->category->id,
        'type_id' => $this->typeExpense->id,
        'payment_method_id' => $this->paymentMethod->id,
    ]);
    $template->users()->sync([$userB->id]);

    actingAs($userA)
        ->getJson("/api/recurring-transactions/{$template->id}")
        ->assertNotFound();

    actingAs($userA)
        ->deleteJson("/api/recurring-transactions/{$template->id}")
        ->assertNotFound();
});
