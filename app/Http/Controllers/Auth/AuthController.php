<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLogin(): Response|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return response()
            ->view('auth.login')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }

    /**
     * Handle authentication using Gmail for admins and NRP/NIP for personil.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ], [
            'login.required' => 'Gmail atau NRP/NIP wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $login = trim($credentials['login']);
        $password = $credentials['password'];
        $remember = $request->boolean('remember');
        $throttleKey = $this->throttleKey($request, $login);

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            return $this->loginErrorResponse(
                'Terlalu banyak percobaan login. Coba lagi dalam '.RateLimiter::availableIn($throttleKey).' detik.'
            );
        }

        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            $response = $this->authenticateByEmail($request, $login, $password, $remember);

            if ($response !== null) {
                return $response;
            }
        } else {
            $response = $this->authenticateByNrpNip($request, $login, $password, $remember);

            if ($response !== null) {
                return $response;
            }
        }

        RateLimiter::hit($throttleKey, 300);

        return $this->loginErrorResponse('Gmail/NRP/NIP atau password salah, atau akun tidak aktif.');
    }

    /**
     * Logout the user.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function authenticateByEmail(Request $request, string $email, string $password, bool $remember): ?RedirectResponse
    {
        $user = User::with('roles')
            ->whereRaw('LOWER(email) = ?', [Str::lower($email)])
            ->first();

        if ($user?->isPersonnel()) {
            return $this->loginErrorResponse('Akun personil harus login menggunakan NRP/NIP.');
        }

        if (! $user) {
            return null;
        }

        return $this->attemptLogin($request, [
            'email' => $user->email,
            'password' => $password,
            'is_active' => true,
        ], $remember);
    }

    private function authenticateByNrpNip(Request $request, string $nrpNip, string $password, bool $remember): ?RedirectResponse
    {
        $user = User::with('roles')
            ->where('nrp_nip', $nrpNip)
            ->first();

        if ($user && $user->usesEmailLogin() && filled($user->email)) {
            return $this->loginErrorResponse('Akun admin harus login menggunakan Gmail yang terdaftar.');
        }

        if (! $user) {
            return null;
        }

        return $this->attemptLogin($request, [
            'nrp_nip' => $user->nrp_nip,
            'password' => $password,
            'is_active' => true,
        ], $remember);
    }

    private function attemptLogin(Request $request, array $credentials, bool $remember): ?RedirectResponse
    {
        if (! Auth::attempt($credentials, $remember)) {
            return null;
        }

        $request->session()->regenerate();
        RateLimiter::clear($this->throttleKey($request, (string) $request->input('login')));

        return redirect()->intended(route('dashboard'));
    }

    private function throttleKey(Request $request, string $login): string
    {
        return Str::lower($login).'|'.$request->ip();
    }

    private function loginErrorResponse(string $message): RedirectResponse
    {
        return back()->withErrors([
            'login' => $message,
        ])->onlyInput('login');
    }
}
