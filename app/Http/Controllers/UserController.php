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
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    private const ROLE_SORT_ORDER = [
        'superadmin' => 1,
        'admin_gudang' => 2,
        'admin_satker' => 3,
    ];

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $query = $this->administrativeUsersQuery();

        $this->applyFilters($query, $request);

        // Pagination
        $perPage = $request->get('per_page', 10);
        $users = $query->latest()->paginate($perPage)->withQueryString();

        // Order by ID, but put 'SISWA' (id 40) just before 'POLRESTA MATARAM' (id 30)
        $satkers = Satker::orderBy('sort_order')->orderBy('name')->get();
        $roles = Role::whereIn('name', User::ADMINISTRATIVE_ROLES)
            ->orderByRaw($this->buildRoleOrderCaseSql())
            ->get();

        // Calculate Stats
        $stats = [
            'total_admin_satker' => $this->countUsersByExistingRoles(['admin_satker']),
            'total_superadmin' => $this->countUsersByExistingRoles(['superadmin']),
            'total_admin_gudang' => $this->countUsersByExistingRoles(['admin_gudang']),
            'total_personil' => $this->countUsersByExistingRoles(['personil']),
            'active_users' => $this->administrativeUsersQuery()->where('is_active', true)->count(),
            'inactive_users' => $this->administrativeUsersQuery()->where('is_active', false)->count(),
        ];

        return view('admin.users.index', compact('users', 'satkers', 'roles', 'stats', 'perPage'));
    }

    private function buildRoleOrderCaseSql(): string
    {
        $cases = collect(self::ROLE_SORT_ORDER)
            ->map(fn (int $position, string $role) => "WHEN '{$role}' THEN {$position}")
            ->implode(' ');

        return "CASE name {$cases} ELSE 999 END";
    }

    private function countUsersByExistingRoles(array $roleNames): int
    {
        $existingRoles = Role::whereIn('name', $roleNames)->pluck('name')->all();

        if ($existingRoles === []) {
            return 0;
        }

        return User::role($existingRoles)->count();
    }

    private function rolesExist(array $roleNames): bool
    {
        return Role::whereIn('name', $roleNames)->count() === count($roleNames);
    }

    private function administrativeUsersQuery()
    {
        return User::with(['satker', 'roles'])
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', User::ADMINISTRATIVE_ROLES);
            });
    }

    private function rejectPersonnelManagement(User $user)
    {
        if (! $user->hasRole('personil')) {
            return null;
        }

        return redirect()
            ->route('admin.users.index')
            ->with('error', 'Akun personil dikelola melalui menu Data Personel.');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request)
    {
        $this->authorize('create', User::class);

        $validated = $request->validated();

        $user = User::create($this->buildUserPayload($validated, true));

        $user->assignRole($validated['role']);

        AuditLogger::log('Tambah User', 'Manajemen Pengguna', $user, null, $user->toArray(), 'success', "Menambah pengguna baru: {$user->name}");

        return redirect()->route('admin.users.index')->with('success', 'User berhasil ditambahkan.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $this->authorize('update', $user);

        if ($response = $this->rejectPersonnelManagement($user)) {
            return $response;
        }

        $validated = $request->validated();
        $user->update($this->buildUserPayload($validated, $request->has('is_active')));

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
        $this->authorize('delete', $user);

        if ($response = $this->rejectPersonnelManagement($user)) {
            return $response;
        }

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

        $columns = ['email', 'name', 'phone', 'role', 'password'];

        $callback = function () use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            // Example row
            fputcsv($file, ['admin.gudang.kapor@gmail.com', 'Admin Gudang', '08123456789', 'admin_gudang', 'Q7@vLp2#']);
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
                $row = $this->parseImportRow($data);

                if ($row === null) {
                    continue;
                }

                if (! $this->validateImportRow($row, $errors)) {
                    $errorCount++;

                    continue;
                }

                try {
                    $user = User::updateOrCreate(
                        ['email' => $row['email']],
                        $this->buildImportedUserPayload($row)
                    );

                    $user->syncRoles([$row['role']]);
                    $successCount++;
                } catch (\Exception $e) {
                    $errorCount++;
                    $errors[] = "Gagal memproses {$row['email']}: ".$e->getMessage();
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

    private function applyFilters($query, Request $request): void
    {
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('nrp_nip', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhereHas('satker', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('role') && $this->rolesExist([$request->role])) {
            $query->role($request->role);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }
    }

    private function buildUserPayload(array $validated, bool $isActive): array
    {
        $payload = [
            'nrp_nip' => $validated['nrp_nip'] ?? null,
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'is_active' => $isActive,
            'satker_id' => $validated['satker_id'] ?? null,
        ];

        if (! empty($validated['password'])) {
            $payload['password'] = Hash::make($validated['password']);
        }

        return $payload;
    }

    private function parseImportRow(array $data): ?array
    {
        if (count($data) < 5) {
            return null;
        }

        return [
            'email' => strtolower(trim($data[0])),
            'name' => trim($data[1]),
            'phone' => trim($data[2]),
            'role' => strtolower(trim($data[3])),
            'password' => trim($data[4]),
        ];
    }

    private function validateImportRow(array $row, array &$errors): bool
    {
        if ($row['email'] === '' || $row['name'] === '') {
            return false;
        }

        if (! filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Gmail {$row['email']} tidak valid.";

            return false;
        }

        if ($row['role'] === 'personil') {
            $errors[] = "Gmail {$row['email']}: Peran 'personil' harus diinput melalui Data Personel.";

            return false;
        }

        if (! in_array($row['role'], User::ADMINISTRATIVE_ROLES, true)) {
            $errors[] = "Role '{$row['role']}' tidak valid.";

            return false;
        }

        $validator = Validator::make($row, [
            'password' => [
                'required',
                'string',
                Password::defaults(),
            ],
        ], [
            'password.required' => 'Password wajib diisi.',
        ]);

        if ($validator->fails()) {
            $errors[] = "Gmail {$row['email']}: ".$validator->errors()->first('password');

            return false;
        }

        return true;
    }

    private function buildImportedUserPayload(array $row): array
    {
        return [
            'name' => $row['name'],
            'phone' => $row['phone'],
            'nrp_nip' => null,
            'password' => Hash::make($row['password']),
            'is_active' => true,
        ];
    }
}
