<?php

namespace App\Enums;

/**
 * Application pipeline status.
 *
 * queued            triaged and ranked, not yet applied
 * applied..offer    an active application
 * rejected/withdrawn/ghosted   closed. ghosted = no reply after ~3 weeks.
 */
enum StatusEnum: string
{
    case Queued = 'queued';
    case Applied = 'applied';
    case Screening = 'screening';
    case Interview = 'interview';
    case Offer = 'offer';
    case Rejected = 'rejected';
    case Withdrawn = 'withdrawn';
    case Ghosted = 'ghosted';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** Status counts as an active application ("Active" view). */
    public function isActive(): bool
    {
        return in_array($this, [self::Applied, self::Screening, self::Interview, self::Offer], true);
    }

    /** Terminal status — no further action expected. */
    public function isClosed(): bool
    {
        return in_array($this, [self::Rejected, self::Withdrawn, self::Ghosted], true);
    }

    /** @return list<string> Backing values of the "Active" view, for whereIn(). */
    public static function activeValues(): array
    {
        return array_column(array_filter(self::cases(), fn (self $status) => $status->isActive()), 'value');
    }

    /** @return list<string> Backing values of the "Closed" view, for whereIn(). */
    public static function closedValues(): array
    {
        return array_column(array_filter(self::cases(), fn (self $status) => $status->isClosed()), 'value');
    }

    /** Phone-UI badge variant for this status (shared by the list and detail views). */
    public function badgeVariant(): string
    {
        return match ($this) {
            self::Queued => 'accent',
            self::Rejected, self::Withdrawn, self::Ghosted => 'destructive',
            default => 'primary',
        };
    }
}
