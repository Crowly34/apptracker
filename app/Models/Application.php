<?php

namespace App\Models;

use App\Enums\StatusEnum;
use App\Enums\TierEnum;
use Database\Factories\ApplicationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

#[Fillable([
    'company',
    'role',
    'posting_url',
    'source',
    'status',
    'tier',
    'applied_at',
    'next_action',
    'next_action_due',
    'notes',
    'resume_path',
    'cover_letter_path',
])]
class Application extends Model
{
    /** @use HasFactory<ApplicationFactory> */
    use HasFactory;

    /**
     * Repeats the migration default so a freshly created model reports `queued`
     * before it is reloaded.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => StatusEnum::Queued->value,
    ];

    /**
     * Back-fills `applied_at` the first time a row leaves `queued`. Sits on the
     * model so every write path (API, MCP) gets it. Update-only: a row created
     * directly at a later status keeps whatever `applied_at` it was given.
     */
    protected static function booted(): void
    {
        static::updating(function (Application $application): void {
            if (
                $application->isDirty('status')
                && $application->status !== StatusEnum::Queued
                && $application->applied_at === null
            ) {
                $application->applied_at = Carbon::today();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => StatusEnum::class,
            'tier' => TierEnum::class,
            'applied_at' => 'date',
            'next_action_due' => 'date',
        ];
    }

    /**
     * The three phone views. Ordering is part of each view's contract, so it
     * lives here rather than being re-derived at every call site.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeQueued(Builder $query): void
    {
        $query->where('status', StatusEnum::Queued->value)
            ->orderByRaw("case tier when 'A' then 1 when 'B' then 2 when 'C' then 3 else 4 end")
            ->orderBy('created_at');
    }

    /** @param  Builder<$this>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->whereIn('status', StatusEnum::activeValues())
            ->orderBy('next_action_due')
            ->orderByDesc('updated_at');
    }

    /** @param  Builder<$this>  $query */
    public function scopeClosed(Builder $query): void
    {
        $query->whereIn('status', StatusEnum::closedValues())
            ->orderByDesc('updated_at');
    }
}
