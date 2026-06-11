<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Web\CategoryBudgetController;
use App\Http\Controllers\Web\CategoryWebController;
use App\Http\Controllers\Web\CreditCardWebController;
use App\Http\Controllers\Web\ImpersonationController;
use App\Http\Controllers\Web\MonthlyDashboardController;
use App\Http\Controllers\Web\PaymentMethodWebController;
use App\Http\Controllers\Web\RecurringTemplateWebController;
use App\Http\Controllers\Web\RecurringTransactionWebController;
use App\Http\Controllers\Web\CardStatementWebController;
use App\Http\Controllers\Web\ExportController;
use App\Http\Controllers\Web\ExportDataController;
use App\Http\Controllers\Web\ImportController;
use App\Http\Controllers\Web\InstallmentPurchaseWebController;
use App\Http\Controllers\Web\SharedExpenseWebController;
use App\Http\Controllers\Web\PartnerInvitationWebController;
use App\Http\Controllers\Web\TransactionWebController;
use App\Http\Controllers\Web\TypeWebController;
use App\Http\Controllers\Web\UserWebController;
use Illuminate\Support\Facades\Route;

// Página inicial
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return view('welcome');
});

// Dashboard padrão (Jetstream) protegido
Route::get('/dashboard', function () {
    return redirect()->route('dashboard.monthly', [
        'year' => now()->year,
        'month' => now()->month,
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/invite/partner/{token}', [PartnerInvitationWebController::class, 'accept'])
    ->name('partners.accept');

Route::get('/dashboard/monthly', [MonthlyDashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard.monthly');

// Rotas de perfil (nome, senha, etc.), protegidas por auth
Route::middleware(['auth'])->group(function () {
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

    Route::get('/transactions/{transaction}/edit', [TransactionWebController::class, 'edit'])->name('transactions.edit');
    // Processa o form de edição
    Route::put('/transactions/{transaction}', [TransactionWebController::class, 'update'])->name('transactions.update');
    // Exclui transação via POST/DELETE (aqui você usa mais a API pra deletar, mas deixou a rota pronta)
    Route::delete('/transactions/{transaction}', [TransactionWebController::class, 'destroy'])->name('transactions.destroy');

    // Contas fixas — rotas legadas redirecionam para /settings/recurring-templates
    Route::redirect('/recurring-transactions', '/settings/recurring-templates', 301)->name('recurring-transactions.index');
    Route::redirect('/recurring-transactions/create', '/settings/recurring-templates/create', 301)->name('recurring-transactions.create');
    Route::get('/recurring-transactions/{id}/edit', fn ($id) => redirect(route('recurring-templates.edit', ['recurring_template' => $id]), 301))->name('recurring-transactions.edit');

    // Exportação CSV de transações
    Route::get('/transactions/export', [ExportController::class, 'transactions'])
        ->name('transactions.export');

    // Exportação JSON para análise em IA
    Route::get('/export/json', [ExportDataController::class, 'json'])
        ->name('export.json');

    // Importação de extrato via IA
    Route::post('/import/analyze', [ImportController::class, 'analyze'])->name('import.analyze');
    Route::post('/import/store', [ImportController::class, 'store'])->name('import.store');

    // Tela web do extrato de cartão (usa CardStatementWebController pra montar Blade cards/statement)
    Route::get('/cards/statement', [CardStatementWebController::class, 'index'])
        ->name('cards.statement.index');

    Route::get('/shared-expenses', [SharedExpenseWebController::class, 'index'])
        ->name('shared-expenses.index');
    Route::patch('/shared-expenses/transactions/{transaction}/participants/{participant}/settle', [SharedExpenseWebController::class, 'settle'])
        ->name('shared-expenses.settle');
    Route::delete('/shared-expenses/transactions/{transaction}/participants/{participant}/settle', [SharedExpenseWebController::class, 'unsettle'])
        ->name('shared-expenses.unsettle');
    Route::redirect('/partner-settlements', '/shared-expenses', 301)->name('partner-settlements.index');

    Route::get('/installment-purchases', [InstallmentPurchaseWebController::class, 'index'])
        ->name('installment-purchases.index');

    Route::middleware('dev')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/users', [UserWebController::class, 'index'])->name('users.index');
        Route::get('/users/{user}/edit', [UserWebController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserWebController::class, 'update'])->name('users.update');
        Route::post('/users/{user}/impersonate', [ImpersonationController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('users.impersonate');
    });

    Route::post('/admin/leave-impersonation', [ImpersonationController::class, 'destroy'])
        ->name('admin.leave-impersonation');
});

// routes/web.php
Route::middleware(['auth'])->prefix('settings')->group(function () {
    Route::get('partners', [PartnerInvitationWebController::class, 'index'])->name('partners.index');
    Route::post('partners/invite', [PartnerInvitationWebController::class, 'invite'])->name('partners.invite');

    Route::resource('categories', CategoryWebController::class)->except('show');
    Route::resource('types', TypeWebController::class)->except('show');
    Route::resource('payment-methods', PaymentMethodWebController::class)->except('show');
    Route::resource('credit-cards', CreditCardWebController::class)->except('show');
    Route::resource('recurring-templates', RecurringTemplateWebController::class)->except('show');

    Route::get('budgets', [CategoryBudgetController::class, 'index'])->name('budgets.index');
    Route::post('budgets', [CategoryBudgetController::class, 'store'])->name('budgets.store');
    Route::delete('budgets/{budget}', [CategoryBudgetController::class, 'destroy'])->name('budgets.destroy');
});


// Rotas de autenticação (login, registro, etc.)
require __DIR__ . '/auth.php';
