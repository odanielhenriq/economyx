<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('payment_methods')
            ->where('slug', 'ch')
            ->whereIn('name', ['Choque', 'choque', 'CHOQUE'])
            ->update(['name' => 'Cheque', 'updated_at' => now()]);
    }

    public function down(): void
    {
        // Correção de rótulo — não revertida.
    }
};
