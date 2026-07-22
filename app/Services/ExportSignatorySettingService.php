<?php

namespace App\Services;

use App\Models\InvoiceSetting;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class ExportSignatorySettingService
{
    public const DEFAULT_SATKER_SIGNATORY_TITLE = '.............................';

    private const GLOBAL_SCOPE = 'global';

    private const SATKER_SCOPE = 'satker';

    /**
     * @var array<int, string>
     */
    private const FIELDS = [
        'signatory_name',
        'signatory_rank',
        'signatory_nrp',
        'signatory_title',
        'location',
        'organization_name',
    ];

    /**
     * Resolve signatory settings for authenticated user.
     *
     * @return array<string, string>
     */
    public function resolveForCurrentUser(): array
    {
        $user = Auth::user();

        return $user instanceof User
            ? $this->resolveForUser($user)
            : $this->getGlobalSettings();
    }

    /**
     * Resolve signatory settings by role and satker scope.
     *
     * @return array<string, string>
     */
    public function resolveForUser(?User $user): array
    {
        $global = $this->getGlobalSettings();

        if (! $user || ! $user->hasRole('admin_satker') || ! $user->satker_id) {
            return $global;
        }

        $satkerValues = $this->getScopeValues(self::SATKER_SCOPE, (int) $user->satker_id);

        return $this->mergePreferScope($satkerValues, $this->satkerFallbackSettings($global));
    }

    /**
     * @return array<string, string>
     */
    public function getGlobalSettings(): array
    {
        $scopeValues = $this->getScopeValues(self::GLOBAL_SCOPE);

        return $this->mergePreferScope($scopeValues, $this->invoiceFallbackValues());
    }

    /**
     * @return array<string, string>
     */
    public function getSatkerSettings(int $satkerId): array
    {
        $scopeValues = $this->getScopeValues(self::SATKER_SCOPE, $satkerId);

        return $this->mergePreferScope($scopeValues, $this->satkerFallbackSettings($this->getGlobalSettings()));
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function updateGlobalSettings(array $values): void
    {
        $this->persistScopeValues(self::GLOBAL_SCOPE, null, $values);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function updateSatkerSettings(int $satkerId, array $values): void
    {
        $this->persistScopeValues(self::SATKER_SCOPE, $satkerId, $values);
    }

    public function applyToInvoiceSetting(InvoiceSetting $invoiceSetting, ?User $user = null): InvoiceSetting
    {
        $resolved = $user
            ? $this->resolveForUser($user)
            : $this->resolveForCurrentUser();

        $invoiceSetting->setAttribute('signatory_name', $resolved['signatory_name']);
        $invoiceSetting->setAttribute('signatory_rank', $resolved['signatory_rank']);
        $invoiceSetting->setAttribute('signatory_nrp', $resolved['signatory_nrp']);
        $invoiceSetting->setAttribute('signatory_title', $resolved['signatory_title']);
        $invoiceSetting->setAttribute('location', $resolved['location']);
        $invoiceSetting->setAttribute('organization_name', $resolved['organization_name']);

        return $invoiceSetting;
    }

    /**
     * @return array<string, string>
     */
    private function getScopeValues(string $scope, ?int $satkerId = null): array
    {
        if (! Schema::hasTable('settings')) {
            return array_fill_keys(self::FIELDS, '');
        }

        $keys = [];
        foreach (self::FIELDS as $field) {
            $keys[$field] = $this->makeKey($scope, $field, $satkerId);
        }

        $rows = Setting::query()
            ->whereIn('key', array_values($keys))
            ->pluck('value', 'key');

        $values = [];
        foreach ($keys as $field => $key) {
            $values[$field] = (string) ($rows[$key] ?? '');
        }

        return $values;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function persistScopeValues(string $scope, ?int $satkerId, array $values): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        foreach (self::FIELDS as $field) {
            if (! array_key_exists($field, $values)) {
                continue;
            }

            Setting::setValue(
                $this->makeKey($scope, $field, $satkerId),
                $this->normalizeValue($values[$field]),
            );
        }
    }

    /**
     * @param  array<string, string>  $preferred
     * @param  array<string, string>  $fallback
     * @return array<string, string>
     */
    private function mergePreferScope(array $preferred, array $fallback): array
    {
        $resolved = [];
        foreach (self::FIELDS as $field) {
            $resolved[$field] = $this->firstNonEmpty(
                $preferred[$field] ?? '',
                $fallback[$field] ?? '',
            );
        }

        return $resolved;
    }

    /**
     * @return array<string, string>
     */
    private function invoiceFallbackValues(): array
    {
        if (! Schema::hasTable('invoice_settings')) {
            return [
                'signatory_name' => '.............................',
                'signatory_rank' => '',
                'signatory_nrp' => '',
                'signatory_title' => 'PEJABAT PEMBUAT KOMITMEN',
                'location' => 'Mataram',
                'organization_name' => 'KEPALA BIRO LOGISTIK POLDA NTB',
            ];
        }

        $invoiceSetting = InvoiceSetting::query()->first();

        return [
            'signatory_name' => $this->firstNonEmpty((string) optional($invoiceSetting)->signatory_name, '.............................'),
            'signatory_rank' => (string) optional($invoiceSetting)->signatory_rank,
            'signatory_nrp' => (string) optional($invoiceSetting)->signatory_nrp,
            'signatory_title' => $this->firstNonEmpty((string) optional($invoiceSetting)->signatory_title, 'PEJABAT PEMBUAT KOMITMEN'),
            'location' => $this->firstNonEmpty((string) optional($invoiceSetting)->location, 'Mataram'),
            'organization_name' => $this->firstNonEmpty((string) optional($invoiceSetting)->organization_name, 'KEPALA BIRO LOGISTIK POLDA NTB'),
        ];
    }

    /**
     * @param  array<string, string>  $global
     * @return array<string, string>
     */
    private function satkerFallbackSettings(array $global): array
    {
        $global['signatory_title'] = self::DEFAULT_SATKER_SIGNATORY_TITLE;

        return $global;
    }

    private function makeKey(string $scope, string $field, ?int $satkerId = null): string
    {
        if ($scope === self::SATKER_SCOPE && $satkerId) {
            return "export_signatory.satker.{$satkerId}.{$field}";
        }

        return "export_signatory.global.{$field}";
    }

    private function normalizeValue(mixed $value): string
    {
        return trim((string) ($value ?? ''));
    }

    private function firstNonEmpty(string ...$values): string
    {
        foreach ($values as $value) {
            if (trim($value) !== '') {
                return $value;
            }
        }

        return '';
    }
}
