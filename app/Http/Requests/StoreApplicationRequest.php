<?php

namespace App\Http\Requests;

use App\Enums\StatusEnum;
use App\Enums\TierEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'company' => ['required', 'string', 'max:255'],
            'role' => ['required', 'string', 'max:255'],
            'posting_url' => ['nullable', 'url', 'max:2048'],
            'source' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::enum(StatusEnum::class)],
            'tier' => ['nullable', Rule::enum(TierEnum::class)],
            'notes' => ['nullable', 'string'],
        ];
    }
}
