<?php

use function Pest\Laravel\getJson;

it('returns a successfull response on /api/transactions', function () {
    $response = getJson('/api/transactions');

    $response->assertOk();
});
