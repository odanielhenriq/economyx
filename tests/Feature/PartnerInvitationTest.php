<?php

use App\Models\User;
use App\Services\PartnerInvitationService;
use Illuminate\Support\Facades\DB;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;

it('creates bidirectional relation when invite is accepted', function () {
    $inviter = User::factory()->create(['email' => 'inviter@test.com']);
    $acceptor = User::factory()->create(['email' => 'partner@test.com', 'onboarding_completed_at' => now()]);

    $service = app(PartnerInvitationService::class);
    $invitation = $service->create($inviter, 'partner@test.com');
    $service->accept($invitation->token, $acceptor);

    expect(DB::table('user_relations')
        ->where('user_id', $inviter->id)
        ->where('related_user_id', $acceptor->id)
        ->exists())->toBeTrue();

    expect(DB::table('user_relations')
        ->where('user_id', $acceptor->id)
        ->where('related_user_id', $inviter->id)
        ->exists())->toBeTrue();
});

it('rejects invite when email does not match', function () {
    $inviter = User::factory()->create();
    $wrongUser = User::factory()->create(['email' => 'wrong@test.com']);

    $service = app(PartnerInvitationService::class);
    $invitation = $service->create($inviter, 'partner@test.com');

    expect(fn () => $service->accept($invitation->token, $wrongUser))
        ->toThrow(\InvalidArgumentException::class);
});

it('can create invite via web form', function () {
    $user = User::factory()->create(['onboarding_completed_at' => now()]);

    actingAs($user)
        ->post(route('partners.invite'), ['email' => 'newpartner@test.com'])
        ->assertRedirect();

    expect(DB::table('partner_invitations')->where('email', 'newpartner@test.com')->exists())->toBeTrue();
});
