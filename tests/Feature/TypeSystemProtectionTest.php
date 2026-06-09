<?php

use App\Models\Type;
use App\Models\User;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\putJson;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('forbids updating system types via api', function () {
    $type = Type::where('slug', 'rc')->firstOrFail();

    actingAs($this->user)
        ->putJson("/api/types/{$type->id}", ['name' => 'Receita alterada'])
        ->assertForbidden()
        ->assertJsonPath('error', 'Este tipo é usado pelo sistema e não pode ser alterado.');

    expect(Type::find($type->id)->name)->not->toBe('Receita alterada');
});

it('forbids deleting system types via api', function () {
    $type = Type::where('slug', 'dc')->firstOrFail();

    actingAs($this->user)
        ->deleteJson("/api/types/{$type->id}")
        ->assertForbidden()
        ->assertJsonPath('error', 'Este tipo é usado pelo sistema e não pode ser excluído.');

    expect(DB::table('types')->where('id', $type->id)->exists())->toBeTrue();
});

it('allows updating custom types via api', function () {
    $type = Type::create(['name' => 'Investimento', 'slug' => 'inv']);

    actingAs($this->user)
        ->putJson("/api/types/{$type->id}", ['name' => 'Investimentos'])
        ->assertOk();

    expect(Type::find($type->id)->name)->toBe('Investimentos');
});

it('allows deleting custom types via api', function () {
    $type = Type::create(['name' => 'Temporário', 'slug' => 'tmp']);

    actingAs($this->user)
        ->deleteJson("/api/types/{$type->id}")
        ->assertNoContent();

    expect(DB::table('types')->where('id', $type->id)->exists())->toBeFalse();
});
