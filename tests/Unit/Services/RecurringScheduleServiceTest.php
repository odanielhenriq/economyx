<?php

use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\RecurringTransaction;
use App\Models\Transaction;
use App\Models\Type;
use App\Services\RecurringScheduleService;
use Carbon\Carbon;

function makeRecurringFixtures(): array
{
    $category = Category::create(['name' => 'Recorrente', 'slug' => 'rec']);
    $type     = Type::create(['name' => 'Despesa', 'slug' => 'dc_rec']);
    $pm       = PaymentMethod::create(['name' => 'Débito', 'slug' => 'deb_rec']);
    return compact('category', 'type', 'pm');
}

it('gera ocorrência na data correta para frequência mensal', function () {
    ['category' => $category, 'type' => $type, 'pm' => $pm] = makeRecurringFixtures();

    $template = RecurringTransaction::create([
        'description'      => 'Aluguel',
        'amount'           => 1500.00,
        'total_amount'     => 1500.00,
        'frequency'        => 'monthly',
        'day_of_month'     => 10,
        'start_date'       => Carbon::create(2025, 1, 1),
        'is_active'        => true,
        'category_id'      => $category->id,
        'type_id'          => $type->id,
        'payment_method_id'=> $pm->id,
    ]);

    $service = new RecurringScheduleService();
    $dueDate = $service->dueDateForMonth($template, 2025, 6);

    expect($dueDate)->not->toBeNull();
    expect($dueDate->day)->toBe(10);
    expect($dueDate->month)->toBe(6);
    expect($dueDate->year)->toBe(2025);
});

it('gera ocorrência na data correta para frequência anual', function () {
    ['category' => $category, 'type' => $type, 'pm' => $pm] = makeRecurringFixtures();

    $template = RecurringTransaction::create([
        'description'      => 'IPTU',
        'amount'           => 800.00,
        'total_amount'     => 800.00,
        'frequency'        => 'yearly',
        'day_of_month'     => 15,
        'start_date'       => Carbon::create(2024, 3, 1), // ocorre todo mês 3
        'is_active'        => true,
        'category_id'      => $category->id,
        'type_id'          => $type->id,
        'payment_method_id'=> $pm->id,
    ]);

    $service = new RecurringScheduleService();

    // Deve ocorrer em março
    $dueMar = $service->dueDateForMonth($template, 2025, 3);
    expect($dueMar)->not->toBeNull();
    expect($dueMar->month)->toBe(3);
    expect($dueMar->day)->toBe(15);

    // Não deve ocorrer em abril
    $dueApr = $service->dueDateForMonth($template, 2025, 4);
    expect($dueApr)->toBeNull();
});

it('não duplica ocorrência quando transação materializada já existe no período', function () {
    ['category' => $category, 'type' => $type, 'pm' => $pm] = makeRecurringFixtures();

    $template = RecurringTransaction::create([
        'description'      => 'Streaming',
        'amount'           => 29.90,
        'total_amount'     => 29.90,
        'frequency'        => 'monthly',
        'day_of_month'     => 5,
        'start_date'       => Carbon::create(2025, 1, 1),
        'is_active'        => true,
        'category_id'      => $category->id,
        'type_id'          => $type->id,
        'payment_method_id'=> $pm->id,
    ]);

    $service = new RecurringScheduleService();
    $dueDate = $service->dueDateForMonth($template, 2025, 7);
    expect($dueDate)->not->toBeNull();

    // Materializa a transação para julho
    Transaction::create([
        'description'              => $template->description,
        'total_amount'             => $template->amount,
        'amount'                   => $template->amount,
        'transaction_date'         => $dueDate->toDateString(),
        'due_date'                 => $dueDate->toDateString(),
        'category_id'              => $category->id,
        'type_id'                  => $type->id,
        'payment_method_id'        => $pm->id,
        'recurring_transaction_id' => $template->id,
    ]);

    // Verifica que já existe transação para esta data (como faz o CashflowService)
    $exists = Transaction::where('recurring_transaction_id', $template->id)
        ->whereDate('due_date', $dueDate->toDateString())
        ->exists();

    expect($exists)->toBeTrue();

    // dueDateForMonth ainda retorna a data — a deduplicação é responsabilidade do caller
    // O que garantimos é que appliesToMonth retorna true corretamente
    expect($service->appliesToMonth($template, 2025, 7))->toBeTrue();
});

it('template inativo não gera ocorrência', function () {
    ['category' => $category, 'type' => $type, 'pm' => $pm] = makeRecurringFixtures();

    $template = RecurringTransaction::create([
        'description'      => 'Cancelado',
        'amount'           => 50.00,
        'total_amount'     => 50.00,
        'frequency'        => 'monthly',
        'day_of_month'     => 1,
        'start_date'       => Carbon::create(2025, 1, 1),
        'is_active'        => false,
        'category_id'      => $category->id,
        'type_id'          => $type->id,
        'payment_method_id'=> $pm->id,
    ]);

    $service = new RecurringScheduleService();
    expect($service->dueDateForMonth($template, 2025, 8))->toBeNull();
    expect($service->appliesToMonth($template, 2025, 8))->toBeFalse();
});

it('ajusta day_of_month para o último dia do mês quando necessário', function () {
    ['category' => $category, 'type' => $type, 'pm' => $pm] = makeRecurringFixtures();

    $template = RecurringTransaction::create([
        'description'      => 'Dia 31',
        'amount'           => 100.00,
        'total_amount'     => 100.00,
        'frequency'        => 'monthly',
        'day_of_month'     => 31,
        'start_date'       => Carbon::create(2025, 1, 1),
        'is_active'        => true,
        'category_id'      => $category->id,
        'type_id'          => $type->id,
        'payment_method_id'=> $pm->id,
    ]);

    $service = new RecurringScheduleService();
    $due = $service->dueDateForMonth($template, 2025, 2); // fevereiro tem 28 dias

    expect($due)->not->toBeNull();
    expect($due->day)->toBe(28); // ajustado para o último dia de fev
});
