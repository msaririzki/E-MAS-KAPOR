<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
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
        return [
            'nrp_nip' => ['required', 'string', 'max:20', Rule::unique('users')->ignore($this->user->id)],
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users')->ignore($this->user->id)],
            'password' => 'nullable|string|min:8',
            'role' => 'required|exists:roles,name',
            'is_active' => 'boolean',
            'satker_id' => 'nullable|exists:satkers,id',
        ];
    }
}
