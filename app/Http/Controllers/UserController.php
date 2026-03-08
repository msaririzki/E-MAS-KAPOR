<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Satker;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = User::with(['satker', 'roles']);

        // Filter Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('nrp_nip', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhereHas('satker', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%");
                    }
                    );
            });
        }

        // Filter Role
        if ($request->filled('role')) {
            $query->role($request->role);
        }

        // Filter Status
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        // Pagination
        $perPage = $request->get('per_page', 10);
        $users = $query->latest()->paginate($perPage)->withQueryString();

        // Order by ID, but put 'SISWA' (id 40) just before 'POLRESTA MATARAM' (id 30)
        $satkers = Satker::orderBy('sort_order')->orderBy('name')->get();
        $roles = Role::where('name', '!=', 'personil')->get();

        // Calculate Stats
        $stats = [
            'total_admin_satker' => User::role('admin_satker')->count(),
            'total_admin_polda' => User::role(['admin', 'superadmin'])->count(),
            'total_personil' => User::role('personil')->count(),
            'active_users' => User::where('is_active', true)->count(),
            'inactive_users' => User::where('is_active', false)->count(),
        ];

        return view('admin.users.index', compact('users', 'satkers', 'roles', 'stats', 'perPage'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request)
    {
        $validated = $request->validated();

        $user = User::create([
            'nrp_nip' => $validated['nrp_nip'],
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'email' => $request->email ?? null,
            'password' => Hash::make($validated['password']),
            'is_active' => true, // Default active for new users
            'satker_id' => $validated['satker_id'] ?? null,
        ]);

        $user->assignRole($validated['role']);

        AuditLogger::log('Tambah User', 'Manajemen Pengguna', $user, null, $user->toArray(), 'success', "Menambah pengguna baru: {$user->name}");

        return redirect()->route('admin.users.index')->with('success', 'User berhasil ditambahkan.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $validated = $request->validated();

        $updateData = [
            'nrp_nip' => $validated['nrp_nip'],
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'],
            'is_active' => $request->has('is_active'),
            'satker_id' => $validated['satker_id'] ?? null,
        ];

        if (! empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        // Update roles
        $user->syncRoles([$validated['role']]);

        AuditLogger::log('Edit User', 'Manajemen Pengguna', $user, null, $user->toArray(), 'success', "Memperbarui data pengguna: {$user->name}");

        return redirect()->route('admin.users.index')->with('success', 'User berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        // Prevent deleting self
        if (auth()->id() === $user->id) {
            return redirect()->route('admin.users.index')->with('error', 'Tidak dapat menghapus akun sendiri.');
        }

        $userName = $user->name;
        $user->delete();

        AuditLogger::log('Hapus User', 'Manajemen Pengguna', null, null, null, 'success', "Menghapus pengguna: {$userName}");

        return redirect()->route('admin.users.index')->with('success', 'User berhasil dihapus.');
    }

    /**
     * Download CSV template for import.
     */
    public function downloadTemplate()
    {
        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=template_import_user.csv',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $columns = ['nrp_nip', 'name', 'phone', 'role', 'password'];

        $callback = function () use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            // Example row
            fputcsv($file, ['ADM123', 'Nama Admin', '08123456789', 'admin', 'password']);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Import users from CSV.
     */
    public function import(Request $request)
    {
        // Prevent timeout for large imports
        set_time_limit(0);

        $request->validate([
            'file' => 'required|mimes:csv,txt|max:2048',
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');

        // Skip header
        fgetcsv($handle);

        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                if (count($data) < 5) {
                    continue;
                }

                $nrp_nip = trim($data[0]);
                $name = trim($data[1]);
                $phone = trim($data[2]);
                $roleName = strtolower(trim($data[3]));
                $password = trim($data[4]);

                if (empty($nrp_nip) || empty($name)) {
                    $errorCount++;

                    continue;
                }

                // Check for 'personil' role restriction
                if ($roleName === 'personil') {
                    $errorCount++;
                    $errors[] = "NRP {$nrp_nip}: Peran 'personil' harus diinput melalui Data Personel.";

                    continue;
                }

                // Role validation
                $role = Role::where('name', $roleName)->first();
                if (! $role) {
                    $errorCount++;
                    $errors[] = "Role '{$roleName}' tidak valid.";

                    continue;
                }

                try {
                    $user = User::updateOrCreate(
                        ['nrp_nip' => $nrp_nip],
                        [
                            'name' => $name,
                            'phone' => $phone,
                            'password' => Hash::make($password),
                            'is_active' => true,
                        ]
                    );

                    $user->syncRoles([$roleName]);
                    $successCount++;
                } catch (\Exception $e) {
                    $errorCount++;
                    $errors[] = "Gagal memproses {$nrp_nip}: ".$e->getMessage();
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->route('admin.users.index')->with('error', 'Terjadi kesalahan sistem saat impor: '.$e->getMessage());
        }

        fclose($handle);

        AuditLogger::log('Import User', 'Manajemen Pengguna', null, null, null, 'success', "Berhasil memproses {$successCount} pengguna. Gagal: {$errorCount}");

        if ($errorCount > 0) {
            return redirect()->route('admin.users.index')->with('warning', "Berhasil memproses {$successCount} data. Gagal: {$errorCount}. Contoh error: ".implode(', ', array_slice($errors, 0, 3)));
        }

        return redirect()->route('admin.users.index')->with('success', "Berhasil mengimpor {$successCount} pengguna.");
    }
}
