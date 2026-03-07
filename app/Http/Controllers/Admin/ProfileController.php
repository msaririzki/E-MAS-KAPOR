<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * Update user theme preference
     */
    public function updateTheme(Request $request)
    {
        $request->validate([
            'theme' => 'required|string|in:theme-default,theme-matcha,theme-cyber,theme-monochrome,theme-twilight',
        ]);

        $user = auth()->user();
        $user->theme = $request->theme;
        $user->save();

        return redirect()->back()->with('success', 'Tema tampilan berhasil diperbarui.');
    }
}
