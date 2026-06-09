<?php

namespace App\Services;

use App\Models\PartnerInvitation;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PartnerInvitationService
{
    public function create(User $inviter, string $email): PartnerInvitation
    {
        $email = strtolower(trim($email));

        if ($email === strtolower($inviter->email)) {
            throw new \InvalidArgumentException('Você não pode convidar a si mesmo.');
        }

        $existingUser = User::where('email', $email)->first();
        if ($existingUser && $this->alreadyLinked($inviter, $existingUser)) {
            throw new \InvalidArgumentException('Este usuário já faz parte da sua rede.');
        }

        PartnerInvitation::where('inviter_user_id', $inviter->id)
            ->where('email', $email)
            ->whereNull('accepted_at')
            ->delete();

        return PartnerInvitation::create([
            'inviter_user_id' => $inviter->id,
            'email' => $email,
            'token' => Str::random(64),
            'relation_type' => 'partner',
            'expires_at' => now()->addDays(7),
        ]);
    }

    public function accept(string $token, User $acceptor): void
    {
        $invitation = PartnerInvitation::where('token', $token)->first();

        if (! $invitation || ! $invitation->isPending()) {
            throw new \InvalidArgumentException('Convite inválido ou expirado.');
        }

        if (strtolower($acceptor->email) !== strtolower($invitation->email)) {
            throw new \InvalidArgumentException('Este convite foi enviado para outro e-mail.');
        }

        if ($this->alreadyLinked($invitation->inviter, $acceptor)) {
            $invitation->update([
                'accepted_at' => now(),
                'accepted_by_user_id' => $acceptor->id,
            ]);

            return;
        }

        DB::transaction(function () use ($invitation, $acceptor) {
            DB::table('user_relations')->insertOrIgnore([
                [
                    'user_id' => $invitation->inviter_user_id,
                    'related_user_id' => $acceptor->id,
                    'relation_type' => $invitation->relation_type,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'user_id' => $acceptor->id,
                    'related_user_id' => $invitation->inviter_user_id,
                    'relation_type' => $invitation->relation_type,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);

            $invitation->update([
                'accepted_at' => now(),
                'accepted_by_user_id' => $acceptor->id,
            ]);
        });
    }

    public function pendingFor(User $user): Collection
    {
        return PartnerInvitation::where('inviter_user_id', $user->id)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->orderByDesc('created_at')
            ->get();
    }

    public function inviteUrl(PartnerInvitation $invitation): string
    {
        return route('partners.accept', ['token' => $invitation->token]);
    }

    private function alreadyLinked(User $a, User $b): bool
    {
        return DB::table('user_relations')
            ->where(function ($q) use ($a, $b) {
                $q->where('user_id', $a->id)->where('related_user_id', $b->id);
            })
            ->orWhere(function ($q) use ($a, $b) {
                $q->where('user_id', $b->id)->where('related_user_id', $a->id);
            })
            ->exists();
    }
}
