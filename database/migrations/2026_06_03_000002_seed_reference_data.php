<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        foreach ($this->types() as $row) {
            if (! DB::table('types')->where('slug', $row['slug'])->exists()) {
                DB::table('types')->insert([...$row, 'created_at' => $now, 'updated_at' => $now]);
            }
        }

        foreach ($this->paymentMethods() as $row) {
            if (! DB::table('payment_methods')->where('slug', $row['slug'])->exists()) {
                DB::table('payment_methods')->insert([...$row, 'created_at' => $now, 'updated_at' => $now]);
            }
        }

        foreach ($this->categories() as $row) {
            if (! DB::table('categories')->where('slug', $row['slug'])->exists()) {
                DB::table('categories')->insert([...$row, 'created_at' => $now, 'updated_at' => $now]);
            }
        }

        // Corrige slug duplicado Casamento (cm → cs) se existir
        DB::table('categories')
            ->where('name', 'Casamento')
            ->where('slug', 'cm')
            ->update(['slug' => 'cs']);
    }

    public function down(): void
    {
        // Dados de referência permanecem — não revertidos.
    }

    private function types(): array
    {
        return [
            ['name' => 'Receita', 'slug' => 'rc'],
            ['name' => 'Despesa', 'slug' => 'dc'],
        ];
    }

    private function paymentMethods(): array
    {
        return [
            ['name' => 'Cartão de Crédito', 'slug' => 'cc'],
            ['name' => 'Cartão de Débito', 'slug' => 'cd'],
            ['name' => 'Pix', 'slug' => 'px'],
            ['name' => 'PayPal', 'slug' => 'pp'],
            ['name' => 'Transferência Bancária', 'slug' => 'tb'],
            ['name' => 'Dinheiro', 'slug' => 'dn'],
            ['name' => 'Cheque', 'slug' => 'ch'],
            ['name' => 'Boleto Bancário', 'slug' => 'bb'],
            ['name' => 'Vale Alimentação', 'slug' => 'va'],
            ['name' => 'Vale Refeição', 'slug' => 'vr'],
        ];
    }

    private function categories(): array
    {
        return [
            ['name' => 'Mercado', 'slug' => 'mc'],
            ['name' => 'Transporte', 'slug' => 'tr'],
            ['name' => 'Assinaturas', 'slug' => 'ss'],
            ['name' => 'Lazer', 'slug' => 'lz'],
            ['name' => 'Comida', 'slug' => 'cm'],
            ['name' => 'Viagens', 'slug' => 'vg'],
            ['name' => 'Educação', 'slug' => 'ed'],
            ['name' => 'Saúde', 'slug' => 'sd'],
            ['name' => 'Contas', 'slug' => 'ct'],
            ['name' => 'Empréstimos', 'slug' => 'ep'],
            ['name' => 'Casamento', 'slug' => 'cs'],
            ['name' => 'Ferramentas', 'slug' => 'fm'],
            ['name' => 'Salário', 'slug' => 'sl'],
            ['name' => 'Freelas', 'slug' => 'fl'],
            ['name' => 'Investimentos', 'slug' => 'iv'],
            ['name' => 'Vestuário', 'slug' => 'vs'],
            ['name' => 'Outros', 'slug' => 'ot'],
        ];
    }
};
