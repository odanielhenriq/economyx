<?php

use App\Http\Controllers\Api\MonthlyDashboardController as MonthlyDashboardApiController;
use App\Http\Controllers\CardStatementController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CreditCardController;
use App\Http\Controllers\PaymentMethodController;
use App\Http\Controllers\RecurringTransactionController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\TypeController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// endpoint padrão de user do Laravel Sanctum (não está sendo usado aqui ainda)
Route::get('/user', function (Request $request) {
    return $request->user();
});

// endpoint simples de teste de "saúde" da API
Route::get('/ping', function (Request $request) {
    return response()->json([
        'status' => 'ok',
        'app' => config('app.name'),
        'time' => now()->toDateTimeString(),
    ]);
});

// CRUD de transações em formato JSON (usado pelo front da index)
Route::get('/transactions', [TransactionController::class, 'index']);
Route::post('/transactions', [TransactionController::class, 'store']);
Route::get('/transactions/{id}', [TransactionController::class, 'show']);
Route::put('/transactions/{id}', [TransactionController::class, 'update']);
Route::delete('/transactions/{id}', [TransactionController::class, 'destroy']);

// CRUD de categorias
Route::get('/categories', [CategoryController::class, 'index']);
Route::post('/categories', [CategoryController::class, 'store']);
Route::get('/categories/{id}', [CategoryController::class, 'show']);
Route::put('/categories/{id}', [CategoryController::class, 'update']);
Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);

// CRUD de tipos
Route::get('/types', [TypeController::class, 'index']);
Route::post('/types', [TypeController::class, 'store']);
Route::get('/types/{id}', [TypeController::class, 'show']);
Route::put('/types/{id}', [TypeController::class, 'update']);
Route::delete('/types/{id}', [TypeController::class, 'destroy']);

// Listagem de usuários (opcionalmente filtrando rede via user_id)
Route::get('/users', [UserController::class, 'index']);

// CRUD de formas de pagamento
Route::get('/payment-methods', [PaymentMethodController::class, 'index']);
Route::post('/payment-methods', [PaymentMethodController::class, 'store']);
Route::get('/payment-methods/{id}', [PaymentMethodController::class, 'show']);
Route::put('/payment-methods/{id}', [PaymentMethodController::class, 'update']);
Route::delete('/payment-methods/{id}', [PaymentMethodController::class, 'destroy']);

// CRUD de cartoes
Route::get('/credit-cards', [CreditCardController::class, 'index']);
Route::post('/credit-cards', [CreditCardController::class, 'store']);
Route::get('/credit-cards/{id}', [CreditCardController::class, 'show']);
Route::put('/credit-cards/{id}', [CreditCardController::class, 'update']);
Route::delete('/credit-cards/{id}', [CreditCardController::class, 'destroy']);

// CRUD de templates recorrentes
Route::get('/recurring-templates', [RecurringTransactionController::class, 'index']);
Route::post('/recurring-templates', [RecurringTransactionController::class, 'store']);
Route::get('/recurring-templates/{id}', [RecurringTransactionController::class, 'show']);
Route::put('/recurring-templates/{id}', [RecurringTransactionController::class, 'update']);
Route::delete('/recurring-templates/{id}', [RecurringTransactionController::class, 'destroy']);

// API de extrato de cartão: /api/cards/{card}/statement?year=2025&month=12
Route::get('cards/{card}/statement', [CardStatementController::class, 'statement']);
// Marca fatura como paga
Route::middleware('auth')->patch('cards/statements/{statement}/pay', [CardStatementController::class, 'markAsPaid']);

// Dashboard mensal em JSON (usado pela tela web via fetch)
Route::middleware(['web'])->get('/dashboard/monthly', [MonthlyDashboardApiController::class, 'index']);
