<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Per-device state for the phone app. Connection details (host, token) are
 * baked into the build from config; the only thing worth persisting on-device
 * is when the local mirror was last refreshed.
 */
#[Fillable(['last_synced_at'])]
class MobileSetting extends Model
{
    protected function casts(): array
    {
        return [
            'last_synced_at' => 'datetime',
        ];
    }

    public static function singleton(): self
    {
        return static::query()->firstOrCreate(['id' => 1]);
    }

    public function markSynced(): void
    {
        $this->forceFill(['last_synced_at' => Carbon::now()])->save();
    }
}
