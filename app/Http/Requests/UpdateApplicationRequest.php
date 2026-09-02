<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateApplicationRequest extends FormRequest
{
    /**
     * The EnsureApiToken middleware is the only auth gate.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Every field is optional (`sometimes`); `company`, `role` and `status`
     * can't be blanked once sent.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [];

        foreach (ApplicationRules::fields() as $field => $constraints) {
            $presence = in_array($field, ['company', 'role', 'status'], true)
                ? ['sometimes', 'required']
                : ['sometimes', 'nullable'];

            $rules[$field] = [...$presence, ...$constraints];
        }

        return $rules;
    }
}
