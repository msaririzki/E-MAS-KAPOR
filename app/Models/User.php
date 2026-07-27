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

    public const READ_ONLY_ADMIN_ROLE = 'kabak_bekum';

    public const SUPERADMIN_READ_ROLES = [
        'superadmin',
        self::READ_ONLY_ADMIN_ROLE,
    ];

    public const SYSTEM_ROLES = [
        'superadmin',
        self::READ_ONLY_ADMIN_ROLE,
        'admin_satker',
        'personil',
        'admin_gudang',
    ];

    public const ADMINISTRATIVE_ROLES = [
        'superadmin',
        self::READ_ONLY_ADMIN_ROLE,
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

    public function itemAllocations(): HasMany
    {
        return $this->hasMany(PersonnelItemAllocation::class);
    }

    public function itemReviews(): HasMany
    {
        return $this->hasMany(ItemReview::class);
    }

    public function isPersonnel(): bool
    {
        return $this->hasRole('personil');
    }

    public function isReadOnlyAdmin(): bool
    {
        return $this->hasRole(self::READ_ONLY_ADMIN_ROLE);
    }

    public function hasSuperadminReadAccess(): bool
    {
        return $this->hasAnyRole(self::SUPERADMIN_READ_ROLES);
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

        return $normalized;
    }

    public static function normalizeLoginIdentifier(mixed $identifier): string
    {
        if ((is_int($identifier) || is_float($identifier)) && $identifier >= 1000000000000000) {
            $identifier = sprintf('%.15g', (float) $identifier);
        }

        $normalized = preg_replace('/[\p{Z}\s\x{FEFF}]+/u', '', (string) $identifier);
        $normalized ??= trim((string) $identifier);
        $normalized = ltrim($normalized, "'");

        if (is_numeric($normalized) && stripos($normalized, 'E') !== false) {
            $normalized = number_format((float) $normalized, 0, '', '');
        }

        return $normalized;
    }

    public static function normalizeWhatsappPhone(?string $phone): ?string
    {
        $normalized = static::normalizePhone($phone);

        if ($normalized === null) {
            return null;
        }

        if (str_starts_with($normalized, '620')) {
            return '62'.substr($normalized, 3);
        }

        if (str_starts_with($normalized, '0')) {
            return '62'.substr($normalized, 1);
        }

        if (str_starts_with($normalized, '8')) {
            return '62'.$normalized;
        }

        return $normalized;
    }

    public static function buildWhatsappLink(?string $phone): ?string
    {
        $normalized = static::normalizeWhatsappPhone($phone);

        return $normalized ? 'https://wa.me/'.$normalized : null;
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
        $identifier = static::normalizeLoginIdentifier($identifier);

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
