<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Web\CategoryWebController;
use App\Http\Controllers\Web\CreditCardWebController;
use App\Http\Controllers\Web\MonthlyDashboardController;
use App\Http\Controllers\Web\PaymentMethodWebController;
use App\Http\Controllers\Web\RecurringTemplateWebController;
use App\Http\Controllers\Web\RecurringTransactionWebController;
use App\Http\Controllers\Web\CardStatementWebController;
use App\Http\Controllers\Web\TransactionWebController;
use App\Http\Controllers\Web\TypeWebController;
use Illuminate\Support\Facades\Route;

// Página inicial (welcome) — ainda não integrada com Economyx de fato
Route::get('/', function () {
    return view('welcome');
});

// Dashboard padrão (Jetstream) protegido
Route::get('/dashboard', function () {
    return redirect()->route('dashboard.monthly', [
        'year' => now()->year,
        'month' => now()->month,
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/dashboard/monthly', [MonthlyDashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard.monthly');

// Rotas de perfil (nome, senha, etc.), protegidas por auth
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');


    // ===== ROTAS WEB DAS TRANSAÇÕES =====
    // Essas rotas retornam BLADE, não JSON.

    // Lista de transações (tela com filtros e tabela que consome a API)
    Route::get('/transactions', [TransactionWebController::class, 'index'])->name('transactions.index');

    // Tela de criar transação (form)
    Route::get('/transactions/create', [TransactionWebController::class, 'create'])->name('transactions.create');
    // Processa o form de criação e redireciona
    Route::post('/transactions', [TransactionWebController::class, 'store'])->name('transactions.store');

    // Tela de editar transação (ainda não construída de verdade, hoje aponta pra view "edit" que é clone da index)
    Route::get('/transactions/{transaction}/edit', [TransactionWebController::class, 'edit'])->name('transactions.edit');
    // Processa o form de edição
    Route::put('/transactions/{transaction}', [TransactionWebController::class, 'update'])->name('transactions.update');
    // Exclui transação via POST/DELETE (aqui você usa mais a API pra deletar, mas deixou a rota pronta)
    Route::delete('/transactions/{transaction}', [TransactionWebController::class, 'destroy'])->name('transactions.destroy');

    // Contas fixas (templates recorrentes)
    Route::resource('recurring-transactions', RecurringTransactionWebController::class)->except('show');

    // Tela web do extrato de cartão (usa CardStatementWebController pra montar Blade cards/statement)
    Route::get('/cards/statement', [CardStatementWebController::class, 'index'])
        ->name('cards.statement.index');


});

// routes/web.php
Route::middleware(['auth'])->prefix('settings')->group(function () {
    Route::resource('categories', CategoryWebController::class)->except('show');
    Route::resource('types', TypeWebController::class)->except('show');
    Route::resource('payment-methods', PaymentMethodWebController::class)->except('show');
    Route::resource('credit-cards', CreditCardWebController::class)->except('show');
    Route::resource('recurring-templates', RecurringTemplateWebController::class)->except('show');
});


// Rotas de autenticação (login, registro, etc.)
require __DIR__ . '/auth.php';
