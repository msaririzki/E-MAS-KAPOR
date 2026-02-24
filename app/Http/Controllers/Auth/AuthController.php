<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Show the NRP/NIP login form.
     */
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    /**
     * Handle authentication using NRP/NIP + password.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'nrp_nip' => 'required|string',
            'password' => 'required|string',
        ], [
            'nrp_nip.required' => 'NRP/NIP wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        if (Auth::attempt([
        'nrp_nip' => $credentials['nrp_nip'],
        'password' => $credentials['password'],
        'is_active' => true,
        ], $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'nrp_nip' => 'NRP/NIP atau password salah, atau akun tidak aktif.',
        ])->onlyInput('nrp_nip');
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
}
