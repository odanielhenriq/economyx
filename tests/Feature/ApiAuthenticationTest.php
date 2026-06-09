<?php

use App\Models\User;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

it('returns 401 for unauthenticated api requests', function () {
    getJson('/api/transactions')->assertUnauthorized();
    getJson('/api/credit-cards')->assertUnauthorized();
    getJson('/api/dashboard/monthly')->assertUnauthorized();
});

it('returns 200 for authenticated api requests', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->getJson('/api/transactions')
        ->assertOk();
});

it('ping endpoint remains public', function () {
    getJson('/api/ping')
        ->assertOk()
        ->assertJsonPath('status', 'ok');
});
