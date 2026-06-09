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

Route::get('/ping', function () {
    return response()->json([
        'status' => 'ok',
        'app' => config('app.name'),
        'time' => now()->toDateTimeString(),
    ]);
});

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/user', fn (Request $request) => $request->user());

    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::post('/transactions', [TransactionController::class, 'store']);
    Route::get('/transactions/{id}', [TransactionController::class, 'show']);
    Route::get('/transactions/{id}/installments', [TransactionController::class, 'installments']);
    Route::post('/transactions/{id}/duplicate', [TransactionController::class, 'duplicate']);
    Route::put('/transactions/{id}', [TransactionController::class, 'update']);
    Route::delete('/transactions/{id}', [TransactionController::class, 'destroy']);

    Route::get('/categories', [CategoryController::class, 'index']);
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::get('/categories/{id}', [CategoryController::class, 'show']);
    Route::put('/categories/{id}', [CategoryController::class, 'update']);
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);

    Route::get('/types', [TypeController::class, 'index']);
    Route::post('/types', [TypeController::class, 'store']);
    Route::get('/types/{id}', [TypeController::class, 'show']);
    Route::put('/types/{id}', [TypeController::class, 'update']);
    Route::delete('/types/{id}', [TypeController::class, 'destroy']);

    Route::get('/users', [UserController::class, 'index']);

    Route::get('/payment-methods', [PaymentMethodController::class, 'index']);
    Route::post('/payment-methods', [PaymentMethodController::class, 'store']);
    Route::get('/payment-methods/{id}', [PaymentMethodController::class, 'show']);
    Route::put('/payment-methods/{id}', [PaymentMethodController::class, 'update']);
    Route::delete('/payment-methods/{id}', [PaymentMethodController::class, 'destroy']);

    Route::get('/credit-cards', [CreditCardController::class, 'index']);
    Route::post('/credit-cards', [CreditCardController::class, 'store']);
    Route::get('/credit-cards/{id}', [CreditCardController::class, 'show']);
    Route::put('/credit-cards/{id}', [CreditCardController::class, 'update']);
    Route::delete('/credit-cards/{id}', [CreditCardController::class, 'destroy']);

    Route::get('/recurring-templates', [RecurringTransactionController::class, 'index']);
    Route::post('/recurring-templates', [RecurringTransactionController::class, 'store']);
    Route::get('/recurring-templates/{id}', [RecurringTransactionController::class, 'show']);
    Route::put('/recurring-templates/{id}', [RecurringTransactionController::class, 'update']);
    Route::delete('/recurring-templates/{id}', [RecurringTransactionController::class, 'destroy']);

    Route::get('cards/{card}/statement', [CardStatementController::class, 'statement']);
    Route::patch('cards/statements/{statement}/pay', [CardStatementController::class, 'markAsPaid']);

    Route::get('/dashboard/monthly', [MonthlyDashboardApiController::class, 'index']);
});
