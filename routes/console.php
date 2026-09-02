<?php

use Illuminate\Support\Facades\Schedule;

// The Markdown files in the notes vault are the source of truth. This keeps the
// DB — and the phone that syncs from it — trailing those files without a manual
// `tracker:import` / `refresh_from_vault` call. `--dry-run` is never used here;
// the import is idempotent and only writes rows that actually changed.
Schedule::command('tracker:import')
    ->everyFifteenMinutes()
    ->withoutOverlapping();
