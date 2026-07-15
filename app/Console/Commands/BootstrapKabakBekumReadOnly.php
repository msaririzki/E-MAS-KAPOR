<?php

namespace App\Console\Commands;

use App\Models\Satker;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class BootstrapKabakBekumReadOnly extends Command
{
    protected $signature = 'app:bootstrap-kabak-bekum
        {email : Gmail akun Kabak Bekum}
        {--name=Kabak Bekum : Nama akun}
        {--password= : Password akun}
        {--generate : Generate password acak kuat}
        {--only-if-missing : Hanya buat akun jika belum ada, jangan rotasi password akun yang sudah ada}';

    protected $description = 'Buat atau rotasi akun Kabak Bekum read-only tanpa menyimpan password di file konfigurasi';

    public function handle(): int
    {
        $email = Str::lower(trim((string) $this->argument('email')));
        $name = trim((string) $this->option('name'));
        $password = $this->resolvePassword();

        if ($password === null) {
            $this->error('Password tidak boleh kosong.');

            return self::FAILURE;
        }

        $satker = Satker::where('code', 'POLDA-NTB')->first();

        if (! $satker) {
            $this->error('Satker POLDA-NTB tidak ditemukan.');

            return self::FAILURE;
        }

        $existingUser = User::where('email', $email)->first();

        if ($existingUser && $this->option('only-if-missing')) {
            $this->info('Akun Kabak Bekum sudah ada. Password tidak dirotasi.');
            $this->line('Email: '.$email);

            return self::SUCCESS;
        }

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name !== '' ? $name : 'Kabak Bekum',
                'nrp_nip' => $existingUser?->nrp_nip,
                'password' => Hash::make($password),
                'satker_id' => $satker->id,
                'is_active' => true,
            ],
        );

        Role::findOrCreate(User::READ_ONLY_ADMIN_ROLE, 'web');
        $user->syncRoles([User::READ_ONLY_ADMIN_ROLE]);

        $this->info('Akun Kabak Bekum read-only siap digunakan.');
        $this->line('Email: '.$email);
        $this->line('Password: '.$password);

        return self::SUCCESS;
    }

    private function resolvePassword(): ?string
    {
        if ($this->option('generate')) {
            return $this->generatePassword();
        }

        $password = (string) ($this->option('password') ?? '');

        if ($password !== '') {
            return $password;
        }

        $password = (string) $this->secret('Masukkan password akun Kabak Bekum');

        return $password !== '' ? $password : null;
    }

    private function generatePassword(): string
    {
        return Str::random(8).'!aA9#'.Str::random(6);
    }
}
