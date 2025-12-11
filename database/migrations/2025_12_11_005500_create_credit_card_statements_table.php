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
        Schema::create('credit_card_statements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credit_card_id')->constrained()->onDelete('cascade');

            $table->integer('year');     // ex: 2025
            $table->integer('month');    // ex: 12

            $table->date('period_start'); // ex: 2025-11-06
            $table->date('period_end');   // ex: 2025-12-05

            $table->integer('closing_day'); // dia de fechamento
            $table->integer('due_day');     // vencimento

            $table->enum('status', ['open', 'closed', 'paid'])->default('open');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_card_statements');
    }
};
