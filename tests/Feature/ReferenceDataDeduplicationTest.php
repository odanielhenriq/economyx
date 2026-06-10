<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

it('deduplicates reference rows by slug on migrate', function () {
    $now = now();

    DB::table('types')->insert([
        ['name' => 'Receita dup', 'slug' => 'rc', 'created_at' => $now, 'updated_at' => $now],
        ['name' => 'Despesa dup', 'slug' => 'dc', 'created_at' => $now, 'updated_at' => $now],
    ]);

    DB::table('categories')->insert([
        ['name' => 'Comida dup', 'slug' => 'cm', 'created_at' => $now, 'updated_at' => $now],
    ]);

    DB::table('payment_methods')->insert([
        ['name' => 'Pix dup', 'slug' => 'px', 'created_at' => $now, 'updated_at' => $now],
    ]);

    expect(DB::table('types')->where('slug', 'rc')->count())->toBe(2);
    expect(DB::table('categories')->where('slug', 'cm')->count())->toBe(2);
    expect(DB::table('payment_methods')->where('slug', 'px')->count())->toBe(2);

    $migration = require database_path('migrations/2026_06_04_000001_deduplicate_reference_data_by_slug.php');
    $migration->up();

    expect(DB::table('types')->where('slug', 'rc')->count())->toBe(1);
    expect(DB::table('types')->where('slug', 'dc')->count())->toBe(1);
    expect(DB::table('categories')->where('slug', 'cm')->count())->toBe(1);
    expect(DB::table('payment_methods')->where('slug', 'px')->count())->toBe(1);
});

it('reference seeders do not duplicate existing slugs', function () {
    $beforeCategories = DB::table('categories')->count();
    $beforeTypes = DB::table('types')->count();
    $beforePaymentMethods = DB::table('payment_methods')->count();

    Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\CategoriesTableSeeder']);
    Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\TypesTableSeeder']);
    Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\PaymentMethodsTableSeeder']);

    expect(DB::table('categories')->count())->toBe($beforeCategories);
    expect(DB::table('types')->count())->toBe($beforeTypes);
    expect(DB::table('payment_methods')->count())->toBe($beforePaymentMethods);
});
