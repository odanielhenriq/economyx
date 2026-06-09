<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\PartnerInvitationService;
use Illuminate\Http\Request;

class PartnerInvitationWebController extends Controller
{
    public function __construct(
        private PartnerInvitationService $invitations
    ) {}

    public function index()
    {
        $user = auth()->user();
        $partners = $user->networkUsers()->reject(fn ($u) => $u->id === $user->id);
        $pending = $this->invitations->pendingFor($user);

        return view('settings.partners.index', compact('partners', 'pending'));
    }

    public function invite(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email|max:255',
        ]);

        try {
            $invitation = $this->invitations->create($request->user(), $data['email']);
            $url = $this->invitations->inviteUrl($invitation);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Convite criado com sucesso.',
                    'url' => $url,
                ]);
            }

            return back()->with('invite_url', $url)->with('success', 'Link de convite gerado!');
        } catch (\InvalidArgumentException $e) {
            if ($request->expectsJson()) {
                return response()->json(['error' => $e->getMessage()], 422);
            }

            return back()->withErrors(['email' => $e->getMessage()]);
        }
    }

    public function accept(string $token)
    {
        if (! auth()->check()) {
            session(['pending_partner_token' => $token]);

            return redirect()->route('register')
                ->with('info', 'Crie sua conta com o e-mail do convite para vincular-se ao parceiro.');
        }

        try {
            $this->invitations->accept($token, auth()->user());

            return redirect()->route('settings.partners.index')
                ->with('success', 'Parceiro vinculado com sucesso!');
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('dashboard')
                ->withErrors(['partner' => $e->getMessage()]);
        }
    }
}
