<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\HandlesUserAccountFields;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    use HandlesUserAccountFields;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->route('user');
        $role = $this->input('role') ?: $user?->getRoleNames()->first();
        $isPersonnel = $role === 'personil';

        return array_merge([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'password' => $this->adminPasswordRules(false),
            'role' => 'required|exists:roles,name',
            'is_active' => 'boolean',
            'satker_id' => 'nullable|exists:satkers,id',
        ], $this->userAccountFieldRules($isPersonnel, $user?->id));
    }

    protected function prepareForValidation(): void
    {
        $this->prepareUserAccountFieldsForValidation();
    }

    public function attributes(): array
    {
        return $this->userAccountAttributes();
    }
}
