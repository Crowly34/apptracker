<?php

namespace App\Http\Requests;

use App\Enums\StatusEnum;
use App\Enums\TierEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'company' => ['sometimes', 'required', 'string', 'max:255'],
            'role' => ['sometimes', 'required', 'string', 'max:255'],
            'posting_url' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'source' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'required', Rule::enum(StatusEnum::class)],
            'tier' => ['sometimes', 'nullable', Rule::enum(TierEnum::class)],
            'applied_at' => ['sometimes', 'nullable', 'date'],
            'next_action' => ['sometimes', 'nullable', 'string', 'max:255'],
            'next_action_due' => ['sometimes', 'nullable', 'date'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'resume_path' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'cover_letter_path' => ['sometimes', 'nullable', 'string', 'max:2048'],
        ];
    }
}
