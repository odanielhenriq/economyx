<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Web\CardStatementWebController;
use App\Http\Controllers\Web\TransactionWebController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/transactions', [TransactionWebController::class, 'index'])->name('transactions.index');

Route::get('/transactions/create', [TransactionWebController::class, 'create'])->name('transactions.create');
Route::post('/transactions', [TransactionWebController::class, 'store'])->name('transactions.store');

Route::get('/transactions/{transaction}/edit', [TransactionWebController::class, 'edit'])->name('transactions.edit');
Route::put('/transactions/{transaction}', [TransactionWebController::class, 'update'])->name('transactions.update');
Route::delete('/transactions/{transaction}', [TransactionWebController::class, 'destroy'])->name('transactions.destroy');

Route::get('/cards/statement', [CardStatementWebController::class, 'index'])
    ->name('cards.statement.index');


require __DIR__ . '/auth.php';
