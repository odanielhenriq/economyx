<?php

use App\Enums\UserRole;
use App\Models\User;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\put;

it('denies regular users access to admin users list', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->get(route('admin.users.index'))
        ->assertForbidden();
});

it('allows dev users to access admin users list', function () {
    $dev = User::factory()->dev()->create();

    actingAs($dev)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee('Usuários');
});

it('allows dev to update another user', function () {
    $dev = User::factory()->dev()->create();
    $target = User::factory()->create([
        'name' => 'Alvo Teste',
        'email' => 'alvo@test.com',
    ]);

    actingAs($dev)
        ->put(route('admin.users.update', $target), [
            'name' => 'Alvo Atualizado',
            'email' => 'alvo@test.com',
            'role' => UserRole::User->value,
        ])
        ->assertRedirect(route('admin.users.index'));

    expect($target->fresh()->name)->toBe('Alvo Atualizado');
});

it('prevents demoting the last dev user', function () {
    $dev = User::factory()->dev()->create(['email' => 'only-dev@test.com']);

    actingAs($dev)
        ->put(route('admin.users.update', $dev), [
            'name' => $dev->name,
            'email' => $dev->email,
            'role' => UserRole::User->value,
        ])
        ->assertSessionHasErrors('role');

    expect($dev->fresh()->role)->toBe(UserRole::Dev);
});

it('allows dev to impersonate a regular user and return', function () {
    $dev = User::factory()->dev()->create();
    $target = User::factory()->create(['name' => 'Usuario Alvo']);

    actingAs($dev)
        ->post(route('admin.users.impersonate', $target))
        ->assertRedirect(route('dashboard'));

    expect(auth()->id())->toBe($target->id);
    expect(session('impersonator_id'))->toBe($dev->id);

    post(route('admin.leave-impersonation'))
        ->assertRedirect(route('admin.users.index'));

    expect(auth()->id())->toBe($dev->id);
    expect(session('impersonator_id'))->toBeNull();
});

it('denies impersonation for regular users', function () {
    $user = User::factory()->create();
    $target = User::factory()->create();

    actingAs($user)
        ->post(route('admin.users.impersonate', $target))
        ->assertForbidden();
});

it('denies impersonating another dev user', function () {
    $dev = User::factory()->dev()->create();
    $otherDev = User::factory()->dev()->create();

    actingAs($dev)
        ->post(route('admin.users.impersonate', $otherDev))
        ->assertForbidden();
});

it('assigns user role by default on registration', function () {
    post(route('register'), [
        'name' => 'Novo Usuario',
        'email' => 'novo@test.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(route('dashboard'));

    $user = User::query()->where('email', 'novo@test.com')->first();

    expect($user)->not->toBeNull();
    expect($user->role)->toBe(UserRole::User);
});
