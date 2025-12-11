<?php

use App\Http\Controllers\CardStatementController;
use App\Http\Controllers\TransactionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// endpoint padrão de user do Laravel Sanctum (não está sendo usado aqui ainda)
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

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

// API de extrato de cartão: /api/cards/{card}/statement?year=2025&month=12
Route::get('cards/{card}/statement', [CardStatementController::class, 'statement']);
