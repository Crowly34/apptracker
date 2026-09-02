<?php

namespace App\Http\Requests;

use App\Enums\StatusEnum;
use App\Enums\TierEnum;
use Illuminate\Validation\Rule;

/**
 * The one place an application field's constraints are defined. The HTTP
 * FormRequests and the MCP tools both build their rule sets from here so the
 * two entry points can't validate the same field differently — a `max` bump or
 * an enum swap lands in both at once.
 *
 * Values are the type/format fragment only; each caller prepends its own
 * presence rule (`required`, `sometimes`, `nullable`) since that differs by
 * endpoint.
 */
final class ApplicationRules
{
    /**
     * @return array<string, list<mixed>>
     */
    public static function fields(): array
    {
        return [
            'company' => ['string', 'max:255'],
            'role' => ['string', 'max:255'],
            'posting_url' => ['url', 'max:2048'],
            'source' => ['string', 'max:255'],
            'status' => [Rule::enum(StatusEnum::class)],
            'tier' => [Rule::enum(TierEnum::class)],
            'applied_at' => ['date'],
            'next_action' => ['string', 'max:255'],
            'next_action_due' => ['date'],
            'notes' => ['string'],
            'resume_path' => ['string', 'max:2048'],
            'cover_letter_path' => ['string', 'max:2048'],
        ];
    }

    /**
     * Constraints for a single field, e.g. `ApplicationRules::for('posting_url')`.
     *
     * @return list<mixed>
     */
    public static function for(string $field): array
    {
        return self::fields()[$field] ?? [];
    }
}
