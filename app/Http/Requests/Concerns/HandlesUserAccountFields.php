<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Validation\Rule;

trait HandlesUserAccountFields
{
    protected function userAccountFieldRules(bool $isPersonnel, ?int $ignoreUserId = null): array
    {
        $emailUniqueRule = Rule::unique('users', 'email');
        $nrpNipUniqueRule = Rule::unique('users', 'nrp_nip');

        if ($ignoreUserId !== null) {
            $emailUniqueRule = $emailUniqueRule->ignore($ignoreUserId);
            $nrpNipUniqueRule = $nrpNipUniqueRule->ignore($ignoreUserId);
        }

        return [
            'email' => [
                $isPersonnel ? 'nullable' : 'required',
                'email',
                'max:255',
                $emailUniqueRule,
            ],
            'nrp_nip' => [
                $isPersonnel ? 'required' : 'nullable',
                'string',
                'max:50',
                $nrpNipUniqueRule,
            ],
        ];
    }

    protected function prepareUserAccountFieldsForValidation(): void
    {
        $this->merge([
            'email' => filled($this->email) ? strtolower(trim((string) $this->email)) : null,
            'nrp_nip' => filled($this->nrp_nip) ? trim((string) $this->nrp_nip) : null,
            'phone' => filled($this->phone) ? trim((string) $this->phone) : null,
            'satker_id' => filled($this->satker_id) ? $this->satker_id : null,
        ]);
    }

    protected function userAccountAttributes(): array
    {
        return [
            'email' => 'Gmail',
            'nrp_nip' => 'NRP/NIP',
            'satker_id' => 'satker',
        ];
    }
}
