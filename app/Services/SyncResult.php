<?php

namespace App\Services;

final readonly class SyncResult
{
    private function __construct(
        public bool $succeeded,
        public string $message,
        public int $count = 0,
    ) {}

    public static function success(int $count): self
    {
        return new self(true, "Synced {$count} applications.", $count);
    }

    public static function failure(string $message): self
    {
        return new self(false, $message);
    }
}
