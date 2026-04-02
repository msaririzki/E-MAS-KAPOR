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

    // ── Scopes ────────────────────────────────────────────────

    public static function createOrUpdatePersonnelAccount(?self $user, string $identifier, string $name, ?int $satkerId, bool $isActive = true): self
    {
        $conflictingUser = static::query()
            ->where('nrp_nip', $identifier)
            ->when($user?->exists, fn ($query) => $query->whereKeyNot($user->id))
            ->first();

        if ($conflictingUser) {
            throw new \RuntimeException("Akun login dengan NRP/NIP {$identifier} sudah digunakan.");
        }

        $user ??= new static;

        $identifierChanged = ! $user->exists || $user->nrp_nip !== $identifier;

        $user->fill([
            'nrp_nip' => $identifier,
            'name' => $name,
            'email' => null,
            'satker_id' => $satkerId,
            'is_active' => $isActive,
        ]);

        if ($identifierChanged) {
            $user->password = Hash::make($identifier);
        }

        $user->save();
        $user->syncRoles(['personil']);

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
