<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;

class UserStatusController extends Controller
{
    public function toggle(User $user)
    {
        if ($user->hasRole('personil')) {
            return back()->with('error', 'Status akun personil dikelola melalui menu Data Personel.');
        }

        // Prevent disabling self
        if (auth()->id() === $user->id) {
            return back()->with('error', 'Tidak dapat menonaktifkan akun sendiri.');
        }

        $user->update([
            'is_active' => ! $user->is_active,
        ]);

        $status = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "User $user->name berhasil $status.");
    }
}
