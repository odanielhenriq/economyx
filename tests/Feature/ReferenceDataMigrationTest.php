<?php

use Illuminate\Support\Facades\DB;

it('seeds reference data on migrate', function () {
    expect(DB::table('types')->where('slug', 'rc')->exists())->toBeTrue();
    expect(DB::table('types')->where('slug', 'dc')->exists())->toBeTrue();
    expect(DB::table('payment_methods')->where('slug', 'cc')->exists())->toBeTrue();
    expect(DB::table('categories')->where('slug', 'cs')->exists())->toBeTrue();
    expect(DB::table('categories')->where('name', 'Comida')->where('slug', 'cm')->exists())->toBeTrue();
});
