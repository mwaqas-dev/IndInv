<?php

namespace App\Http\Controllers;

use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request, AuditService $audit)
    {
        $data = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        $key = strtolower($data['email']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['email' => 'Too many attempts. Try again in '.RateLimiter::availableIn($key).' seconds.']);
        }
        if (! Auth::attempt(['email' => $data['email'], 'password' => $data['password'], 'is_active' => true], $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);
            $audit->record('LOGIN_FAILED', 'Security', 'Failed sign in attempt', null, [], [], 'DENIED', ['email' => $data['email']]);
            throw ValidationException::withMessages(['email' => 'The provided credentials are invalid or this account is inactive.']);
        }
        RateLimiter::clear($key);
        $request->session()->regenerate();
        $request->user()->update(['last_login_at' => now()]);
        $audit->record('LOGIN', 'Security', 'User signed in');

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request, AuditService $audit)
    {
        $audit->record('LOGOUT', 'Security', 'User signed out');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
