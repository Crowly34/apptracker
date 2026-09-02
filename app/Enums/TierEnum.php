<?php

namespace App\Enums;

/**
 * Fit tier from job triage. A = apply now, B = apply if capacity,
 * C = only if nothing better. Nullable on the model — not every row is triaged.
 */
enum TierEnum: string
{
    case A = 'A';
    case B = 'B';
    case C = 'C';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
