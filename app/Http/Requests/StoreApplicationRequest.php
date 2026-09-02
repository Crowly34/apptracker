<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreApplicationRequest extends FormRequest
{
    /**
     * The EnsureApiToken middleware is the only auth gate.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'company' => ['required', ...ApplicationRules::for('company')],
            'role' => ['required', ...ApplicationRules::for('role')],
            'posting_url' => ['nullable', ...ApplicationRules::for('posting_url')],
            'source' => ['nullable', ...ApplicationRules::for('source')],
            'status' => ['nullable', ...ApplicationRules::for('status')],
            'tier' => ['nullable', ...ApplicationRules::for('tier')],
            'notes' => ['nullable', ...ApplicationRules::for('notes')],
        ];
    }
}
