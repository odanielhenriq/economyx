<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transaction_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained()->onDelete('cascade');
            $table->foreignId('credit_card_statement_id')
                ->nullable()
                ->constrained('credit_card_statements')
                ->onDelete('set null');

            $table->integer('installment_number'); // 1
            $table->integer('installment_total');  // 12
            $table->decimal('amount', 15, 2);

            $table->integer('year');
            $table->integer('month'); // mês da fatura dessa parcela
              $table->date('due_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_installments');
    }
};
