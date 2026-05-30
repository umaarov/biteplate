<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Application\Auth\CurrentStaff;
use App\Domain\Staff\Role;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

final class AuthController extends Controller
{
    public function __construct(private readonly CurrentStaff $current)
    {
    }

    public function show(): View|RedirectResponse
    {
        if ($this->current->check()) {
            return redirect()->route('floor');
        }

        return view('auth.login', [
            'devLogin' => (bool) config('biteplate.dev_login', true),
            'keycloakEnabled' => config('services.keycloak.client_id') !== null,
            'roles' => Role::cases(),
        ]);
    }

    /** Local development sign-in: pick a role, no password. Disabled in production. */
    public function devLogin(Request $request): RedirectResponse
    {
        abort_unless((bool) config('biteplate.dev_login', true), 403);

        $validated = $request->validate([
            'role' => ['required', 'string'],
        ]);

        $role = Role::tryFrom($validated['role']) ?? Role::Waiter;
        $this->current->login($role, 'EMP-'.strtoupper($role->value), $role->label().' (demo)');

        return redirect()->route('floor');
    }

    public function keycloakRedirect(): RedirectResponse
    {
        return Socialite::driver('keycloak')->redirect();
    }

    /** Maps a Keycloak realm role from the OIDC token onto a BitePlate {@see Role}. */
    public function keycloakCallback(): RedirectResponse
    {
        try {
            $user = Socialite::driver('keycloak')->user();
        } catch (Throwable) {
            return redirect()->route('login')->withErrors(['oidc' => 'Keycloak sign-in failed.']);
        }

        $raw = (array) $user->user;
        $realmRoles = $raw['realm_access']['roles'] ?? [];

        $role = Role::Waiter;
        foreach ($realmRoles as $candidate) {
            if ($mapped = Role::tryFrom(strtolower((string) $candidate))) {
                $role = $mapped;
                break;
            }
        }

        $this->current->login(
            $role,
            (string) ($user->getId() ?? $user->getNickname() ?? 'kc-user'),
            (string) ($user->getName() ?? $user->getNickname() ?? 'Staff'),
        );

        return redirect()->route('floor');
    }

    public function logout(): RedirectResponse
    {
        $this->current->logout();

        return redirect()->route('login');
    }
}
