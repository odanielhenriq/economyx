<?php

use function Pest\Laravel\getJson;

it('returns unauthorized without authentication', function () {
    getJson('/api/transactions')->assertUnauthorized();
});
