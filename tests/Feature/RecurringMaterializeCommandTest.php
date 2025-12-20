<?php

use App\Models\Category;
use App\Models\CreditCard;
use App\Models\PaymentMethod;
use App\Models\RecurringTransaction;
use App\Models\Transaction;
use App\Models\Type;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;

it('materializa recorrencias sem duplicar no mesmo mes', function () {
    $user = User::factory()->create();
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

    $template->users()->sync([$user->id]);

    $year = (int) now()->year;
    $month = (int) now()->month;

    Artisan::call('recurring:materialize', ['--year' => $year, '--month' => $month]);
    Artisan::call('recurring:materialize', ['--year' => $year, '--month' => $month]);

    expect(Transaction::where('recurring_transaction_id', $template->id)->count())->toBe(1);
});

it('nao materializa meses futuros para recorrencia com cartao', function () {
    $user = User::factory()->create();
    $category = Category::create(['name' => 'Streaming', 'slug' => 'streaming']);
    $type = Type::create(['name' => 'Despesa', 'slug' => 'dc']);
    $paymentMethod = PaymentMethod::create(['name' => 'Cartao', 'slug' => 'cc']);
    $card = CreditCard::create([
        'name' => 'Nubank',
        'owner_user_id' => $user->id,
        'closing_day' => 10,
        'due_day' => 17,
        'is_shared' => true,
    ]);

    $template = RecurringTransaction::create([
        'description' => 'Netflix',
        'amount' => 39.90,
        'total_amount' => 39.90,
        'frequency' => 'monthly',
        'day_of_month' => 5,
        'start_date' => Carbon::now()->startOfMonth(),
        'is_active' => true,
        'category_id' => $category->id,
        'type_id' => $type->id,
        'payment_method_id' => $paymentMethod->id,
        'credit_card_id' => $card->id,
    ]);

    $template->users()->sync([$user->id]);

    $future = Carbon::now()->addMonthNoOverflow();

    Artisan::call('recurring:materialize', [
        '--year' => $future->year,
        '--month' => $future->month,
    ]);

    expect(Transaction::where('recurring_transaction_id', $template->id)->count())->toBe(0);
});
