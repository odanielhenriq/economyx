<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreditCardRequest;
use App\Models\CreditCard;
use App\Models\User;
use App\Repositories\CreditCardRepositoryInterface;
use Illuminate\Support\Facades\Auth;

class CreditCardWebController extends Controller
{
    public function __construct(
        private CreditCardRepositoryInterface $creditCards
    ) {}

    public function index()
    {
        return view('settings.credit-cards.index');
    }

    public function create()
    {
        $users = $this->getNetworkUsers();

        return view('settings.credit-cards.create', compact('users'));
    }

    public function store(CreditCardRequest $request)
    {
        $users = $this->getNetworkUsers();

        $data = $request->validated();
        $data['is_shared'] = $request->boolean('is_shared');

        if (!empty($data['owner_user_id'])) {
            $owner = $users->firstWhere('id', (int) $data['owner_user_id']);
            $data['owner_name'] = $owner?->name;
        }

        $sharedUserIds = $data['shared_user_ids'] ?? [];
        unset($data['shared_user_ids']);

        if (!empty($data['owner_user_id'])) {
            $sharedUserIds[] = (int) $data['owner_user_id'];
        }

        $sharedUserIds = array_values(array_unique($sharedUserIds));

        $this->creditCards->create($data, $sharedUserIds);

        return redirect()
            ->route('credit-cards.index')
            ->with('success', 'Cartão criado com sucesso!');
    }

    public function edit(CreditCard $creditCard)
    {
        $users = $this->getNetworkUsers();
        $creditCard->load('users');

        return view('settings.credit-cards.edit', compact('creditCard', 'users'));
    }

    public function update(CreditCardRequest $request, CreditCard $creditCard)
    {
        $users = $this->getNetworkUsers();

        $data = $request->validated();
        $data['is_shared'] = $request->boolean('is_shared');

        if (!empty($data['owner_user_id'])) {
            $owner = $users->firstWhere('id', (int) $data['owner_user_id']);
            $data['owner_name'] = $owner?->name;
        }

        $sharedUserIds = $data['shared_user_ids'] ?? [];
        unset($data['shared_user_ids']);

        if (!empty($data['owner_user_id'])) {
            $sharedUserIds[] = (int) $data['owner_user_id'];
        }

        $sharedUserIds = array_values(array_unique($sharedUserIds));

        $this->creditCards->update($creditCard->id, $data, $sharedUserIds);

        return redirect()
            ->route('credit-cards.index')
            ->with('success', 'Cartão atualizado com sucesso!');
    }

    public function destroy(CreditCard $creditCard)
    {
        $this->creditCards->delete($creditCard->id);

        return redirect()
            ->route('credit-cards.index')
            ->with('success', 'Cartão removido com sucesso!');
    }

    private function getNetworkUsers()
    {
        /** @var User $viewer */
        $viewer = Auth::user();

        return $viewer->networkUsers();
    }

}
