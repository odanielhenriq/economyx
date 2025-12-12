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
        Schema::create('recurring_transactions', function (Blueprint $table) {
            $table->id();

            $table->string('description');
            $table->decimal('amount', 15, 2);
            $table->decimal('total_amount', 15, 2)->nullable(); // se quiser manter conceito de total

            // regra de recorrência
            $table->enum('frequency', ['monthly', 'yearly']); // começa simples
            $table->unsignedTinyInteger('day_of_month')->nullable(); // dia do vencimento (10, 15, 28...)

            $table->date('start_date')->nullable(); // quando começa
            $table->date('end_date')->nullable();   // quando termina (pode ser null = até cancelar)
            $table->boolean('is_active')->default(true);

            // mesmas chaves da transaction
            $table->foreignId('category_id')->constrained();
            $table->foreignId('type_id')->constrained();
            $table->foreignId('payment_method_id')->constrained();
            $table->foreignId('credit_card_id')->nullable()->constrained('credit_cards');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurring_transactions');
    }
};
