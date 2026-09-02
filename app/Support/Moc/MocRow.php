<?php

namespace App\Support\Moc;

use App\Enums\StatusEnum;
use App\Enums\TierEnum;

/**
 * One pipeline row lifted out of a Markdown source file, normalized to the DB's shape.
 */
readonly class MocRow
{
    public function __construct(
        public string $company,
        public string $role,
        public StatusEnum $status,
        public ?string $appliedAt = null,
        public ?TierEnum $tier = null,
        public ?string $postingUrl = null,
    ) {}

    /**
     * Whether this row and another describe the same posting. The two files
     * disagree on punctuation, parentheticals and trailing job IDs — "Acme" vs
     * "Acme — Acme Group Inc", "…(PHP / MariaDB)" vs "…(PHP / MariaDB)
     * IRC301765" — so a match is one loosened string containing the other, on
     * both company and role.
     */
    public function matches(self $other): bool
    {
        return self::overlap($this->company, $other->company)
            && self::overlap($this->role, $other->role);
    }

    private static function overlap(string $a, string $b): bool
    {
        $a = self::loosen($a);
        $b = self::loosen($b);

        return $a !== '' && $b !== '' && (str_contains($a, $b) || str_contains($b, $a));
    }

    public static function loosen(string $value): string
    {
        return (string) preg_replace('/[^a-z0-9]/', '', mb_strtolower($value));
    }
}
