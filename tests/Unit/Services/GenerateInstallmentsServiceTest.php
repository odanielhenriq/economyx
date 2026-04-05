<?php

use App\Models\Category;
use App\Models\CreditCard;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Models\TransactionInstallment;
use App\Models\Type;
use App\Models\User;
use Carbon\Carbon;

// Helpers shared across tests in this file
function makeBaseFixtures(): array
{
    $user     = User::factory()->create();
    $category = Category::create(['name' => 'Gerais', 'slug' => 'gerais']);
    $type     = Type::create(['name' => 'Despesa', 'slug' => 'dc']);
    $pm       = PaymentMethod::create(['name' => 'Cartão', 'slug' => 'cc']);
    $card     = CreditCard::create([
        'name'         => 'Nubank',
        'closing_day'  => 5,
        'due_day'      => 15,
        'owner_user_id'=> $user->id,
        'is_shared'    => false,
    ]);

    return compact('user', 'category', 'type', 'pm', 'card');
}

// --- GenerateInstallmentsService ---

it('gera o número correto de installments para compra parcelada no cartão', function () {
    ['category' => $category, 'type' => $type, 'pm' => $pm, 'card' => $card] = makeBaseFixtures();

    $tx = Transaction::create([
        'description'      => 'Notebook 10x',
        'total_amount'     => 3000.00,
        'amount'           => 300.00,
        'transaction_date' => '2025-01-10',
        'installment_total'=> 10,
        'category_id'      => $category->id,
        'type_id'          => $type->id,
        'payment_method_id'=> $pm->id,
        'credit_card_id'   => $card->id,
    ]);

    expect($tx->installments()->count())->toBe(10);
});

it('cada installment tem o valor por parcela informado na transação', function () {
    ['category' => $category, 'type' => $type, 'pm' => $pm, 'card' => $card] = makeBaseFixtures();

    $tx = Transaction::create([
        'description'      => 'TV 3x',
        'total_amount'     => 1500.00,
        'amount'           => 500.00,
        'transaction_date' => '2025-02-10',
        'installment_total'=> 3,
        'category_id'      => $category->id,
        'type_id'          => $type->id,
        'payment_method_id'=> $pm->id,
        'credit_card_id'   => $card->id,
    ]);

    $tx->installments->each(function ($inst) {
        expect((float) $inst->amount)->toBe(500.00);
    });
});

it('datas de vencimento das parcelas respeitam o due_day do cartão', function () {
    ['category' => $category, 'type' => $type, 'pm' => $pm, 'card' => $card] = makeBaseFixtures();
    // card tem due_day = 15

    $tx = Transaction::create([
        'description'      => 'Celular 2x',
        'total_amount'     => 2000.00,
        'amount'           => 1000.00,
        'transaction_date' => '2025-03-01',
        'installment_total'=> 2,
        'category_id'      => $category->id,
        'type_id'          => $type->id,
        'payment_method_id'=> $pm->id,
        'credit_card_id'   => $card->id,
    ]);

    $tx->installments->each(function ($inst) {
        expect((int) Carbon::parse($inst->due_date)->day)->toBe(15);
    });
});

it('compra à vista (installment_total = 1) não gera installments', function () {
    ['category' => $category, 'type' => $type, 'pm' => $pm, 'card' => $card] = makeBaseFixtures();

    $tx = Transaction::create([
        'description'      => 'Compra à vista',
        'total_amount'     => 100.00,
        'amount'           => 100.00,
        'transaction_date' => '2025-01-10',
        'installment_total'=> 1,
        'category_id'      => $category->id,
        'type_id'          => $type->id,
        'payment_method_id'=> $pm->id,
        'credit_card_id'   => $card->id,
    ]);

    expect($tx->installments()->count())->toBe(0);
});

it('compra sem cartão de crédito não gera installments', function () {
    $user     = User::factory()->create();
    $category = Category::create(['name' => 'Alimentação', 'slug' => 'al']);
    $type     = Type::create(['name' => 'Despesa', 'slug' => 'dc2']);
    $pm       = PaymentMethod::create(['name' => 'Dinheiro', 'slug' => 'dn']);

    $tx = Transaction::create([
        'description'      => 'Mercado',
        'total_amount'     => 200.00,
        'amount'           => 200.00,
        'transaction_date' => '2025-01-10',
        'category_id'      => $category->id,
        'type_id'          => $type->id,
        'payment_method_id'=> $pm->id,
        'credit_card_id'   => null,
    ]);

    expect($tx->installments()->count())->toBe(0);
});
