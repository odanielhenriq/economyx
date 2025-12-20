<?php

namespace App\Providers;

use App\Repositories\CategoryRepository;
use App\Repositories\CategoryRepositoryInterface;
use App\Repositories\CreditCardRepository;
use App\Repositories\CreditCardRepositoryInterface;
use App\Repositories\PaymentMethodRepository;
use App\Repositories\PaymentMethodRepositoryInterface;
use App\Repositories\RecurringTransactionRepository;
use App\Repositories\RecurringTransactionRepositoryInterface;
use App\Repositories\TransactionRepository;
use App\Repositories\TransactionRepositoryInterface;
use App\Repositories\TypeRepository;
use App\Repositories\TypeRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            TransactionRepositoryInterface::class,
            TransactionRepository::class
        );

        $this->app->bind(
            CategoryRepositoryInterface::class,
            CategoryRepository::class
        );

        $this->app->bind(
            TypeRepositoryInterface::class,
            TypeRepository::class
        );

        $this->app->bind(
            PaymentMethodRepositoryInterface::class,
            PaymentMethodRepository::class
        );

        $this->app->bind(
            CreditCardRepositoryInterface::class,
            CreditCardRepository::class
        );

        $this->app->bind(
            RecurringTransactionRepositoryInterface::class,
            RecurringTransactionRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
