<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    public const IMPORT_PASSWORD_ROUNDS = 4;

    public const SYSTEM_ROLES = [
        'superadmin',
        'admin_satker',
        'personil',
        'admin_gudang',
    ];

    public const ADMINISTRATIVE_ROLES = [
        'superadmin',
        'admin_gudang',
        'admin_satker',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nrp_nip',
        'name',
        'phone',
        'email',
        'password',
        'satker_id',
        'is_active',
        'theme',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    // ── Relationships ─────────────────────────────────────────

    public function satker(): BelongsTo
    {
        return $this->belongsTo(Satker::class);
    }

    public function personnel(): HasOne
    {
        return $this->hasOne(Personnel::class);
    }

    public function testimonials(): HasMany
    {
        return $this->hasMany(Testimonial::class);
    }

    public function isPersonnel(): bool
    {
        return $this->hasRole('personil');
    }

    public function usesEmailLogin(): bool
    {
        return ! $this->isPersonnel();
    }

    public function loginIdentifier(): ?string
    {
        return $this->usesEmailLogin() ? $this->email : $this->nrp_nip;
    }

    public function loginIdentifierLabel(): string
    {
        return $this->usesEmailLogin() ? 'Gmail' : 'NRP/NIP';
    }

    public static function normalizePhone(?string $phone): ?string
    {
        $normalized = preg_replace('/\D+/', '', trim((string) $phone)) ?? '';

        if ($normalized === '') {
            return null;
        }

        if (str_starts_with($normalized, '620')) {
            $normalized = '62'.substr($normalized, 3);
        } elseif (str_starts_with($normalized, '0')) {
            $normalized = '62'.substr($normalized, 1);
        } elseif (str_starts_with($normalized, '8')) {
            $normalized = '62'.$normalized;
        }

        return $normalized;
    }

    // ── Scopes ────────────────────────────────────────────────

    public static function createOrUpdatePersonnelAccount(
        ?self $user,
        string $identifier,
        string $name,
        ?int $satkerId,
        bool $isActive = true,
        ?string $phone = null,
        bool $syncPhone = false,
    ): self {
        return static::createOrUpdatePersonnelAccountWithOptions($user, $identifier, $name, $satkerId, $isActive, null, $phone, $syncPhone);
    }

    public static function createOrUpdatePersonnelImportAccount(
        ?self $user,
        string $identifier,
        string $name,
        ?int $satkerId,
        bool $isActive = true,
        ?string $phone = null,
        bool $syncPhone = false,
    ): self {
        return static::createOrUpdatePersonnelAccountWithOptions(
            $user,
            $identifier,
            $name,
            $satkerId,
            $isActive,
            static::IMPORT_PASSWORD_ROUNDS,
            $phone,
            $syncPhone,
        );
    }

    private static function createOrUpdatePersonnelAccountWithOptions(
        ?self $user,
        string $identifier,
        string $name,
        ?int $satkerId,
        bool $isActive = true,
        ?int $passwordRounds = null,
        ?string $phone = null,
        bool $syncPhone = false,
    ): self {
        $conflictingUser = static::query()
            ->where('nrp_nip', $identifier)
            ->when($user?->exists, fn ($query) => $query->whereKeyNot($user->id))
            ->first();

        if ($conflictingUser) {
            throw new \RuntimeException("Akun login dengan NRP/NIP {$identifier} sudah digunakan.");
        }

        $user ??= new static;

        $identifierChanged = ! $user->exists || $user->nrp_nip !== $identifier;

        $attributes = [
            'nrp_nip' => $identifier,
            'name' => $name,
            'email' => null,
            'satker_id' => $satkerId,
            'is_active' => $isActive,
        ];

        if ($syncPhone) {
            $attributes['phone'] = static::normalizePhone($phone);
        }

        $user->fill($attributes);

        if ($identifierChanged) {
            $user->password = $passwordRounds === null
                ? Hash::make($identifier)
                : Hash::make($identifier, ['rounds' => $passwordRounds]);
        }

        $user->save();
        $user->loadMissing('roles');

        if ($user->roles->count() !== 1 || $user->roles->first()?->name !== 'personil') {
            $user->syncRoles(['personil']);
        }

        return $user;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeBySatker($query, int $satkerId)
    {
        return $query->where('satker_id', $satkerId);
    }
}
