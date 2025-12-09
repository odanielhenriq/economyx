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
        Schema::create('credit_cards', function (Blueprint $table) {
            $table->id();
            $table->string('name');          // Santander, Nubank, Magalu, Will...
            $table->string('alias')->nullable(); // apelido interno se quiser
            $table->integer('closing_day')->nullable(); // dia de fechamento (ex: 10)
            $table->integer('due_day')->nullable();     // dia de vencimento (ex: 17)
            $table->decimal('limit', 10, 2)->nullable(); // opcional
            $table->foreignId('owner_user_id')
                ->nullable()
                ->after('id')
                ->constrained('users')
                ->nullOnDelete();

            $table->string('owner_name')
                ->nullable()
                ->after('name');

            $table->boolean('is_shared')
                ->default(true)
                ->after('owner_user_id');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_cards');
    }
};
