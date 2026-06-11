<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SharedExpenseSettlementService
{
    public function __construct(
        private SharedExpenseService $sharedExpenses
    ) {}

    public function settle(Transaction $transaction, User $participant, User $actor): void
    {
        $this->assertCanManageSettlement($transaction, $participant, $actor, settling: true);

        $payerId = $this->sharedExpenses->resolvePayerId($transaction);

        if ($payerId === null) {
            throw new InvalidArgumentException('Não foi possível identificar quem pagou este gasto.');
        }

        if ((int) $participant->id === (int) $payerId) {
            throw new InvalidArgumentException('A parte de quem pagou não precisa ser acertada.');
        }

        DB::table('transaction_user')
            ->where('transaction_id', $transaction->id)
            ->where('user_id', $participant->id)
            ->update([
                'is_settled' => true,
                'settled_at' => now(),
                'settled_by_user_id' => $actor->id,
                'settled_to_user_id' => $payerId,
                'updated_at' => now(),
            ]);
    }

    public function unsettle(Transaction $transaction, User $participant, User $actor): void
    {
        $transaction->refresh();
        $transaction->load(['users', 'type', 'creditCard.owner']);

        $this->assertCanManageSettlement($transaction, $participant, $actor, settling: false);

        $payerId = $this->sharedExpenses->resolvePayerId($transaction);

        if ($payerId !== null && (int) $participant->id === (int) $payerId) {
            throw new InvalidArgumentException('A parte de quem pagou não pode ser desfeita.');
        }

        DB::table('transaction_user')
            ->where('transaction_id', $transaction->id)
            ->where('user_id', $participant->id)
            ->update([
                'is_settled' => false,
                'settled_at' => null,
                'settled_by_user_id' => null,
                'settled_to_user_id' => null,
                'settlement_note' => null,
                'updated_at' => now(),
            ]);
    }

    private function assertCanManageSettlement(
        Transaction $transaction,
        User $participant,
        User $actor,
        bool $settling
    ): void {
        $transaction->loadMissing(['users', 'type', 'creditCard.owner']);

        if ($transaction->type?->slug !== 'dc' || $transaction->users->count() < 2) {
            throw new AuthorizationException('Este lançamento não é um gasto compartilhado.');
        }

        $networkIds = $actor->networkIds();

        if (! in_array($actor->id, $networkIds, true)) {
            throw new AuthorizationException('Acesso negado.');
        }

        if (! $transaction->users->contains('id', $actor->id)) {
            throw new AuthorizationException('Você não participa deste gasto.');
        }

        if (! $transaction->users->contains('id', $participant->id)) {
            throw new AuthorizationException('Participante inválido para este gasto.');
        }

        if (! $transaction->users->every(fn (User $user) => in_array($user->id, $networkIds, true))) {
            throw new AuthorizationException('Este gasto inclui alguém fora da sua rede.');
        }

        $payerId = $this->sharedExpenses->resolvePayerId($transaction);
        $pivot = $transaction->users->firstWhere('id', $participant->id)?->pivot;
        $isSettled = (bool) ($pivot->is_settled ?? false);

        if ($settling && $isSettled) {
            throw new InvalidArgumentException('Esta parte já foi marcada como acertada.');
        }

        if (! $settling && ! $isSettled) {
            throw new InvalidArgumentException('Esta parte ainda não foi acertada.');
        }

        $isOwnPart = (int) $participant->id === (int) $actor->id;
        $isPayer = $payerId !== null && (int) $actor->id === (int) $payerId;

        if (! $isOwnPart && ! $isPayer) {
            throw new AuthorizationException('Você só pode acertar a sua parte ou a parte de quem deve a você.');
        }
    }
}
