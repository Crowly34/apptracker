<?php

$vault = env('APPTRACKER_VAULT_PATH');

return [

    // Static bearer token for every /api route. Single user on a trusted
    // network, so Sanctum + a users table would be overkill. Sent as
    // "Authorization: Bearer <token>".
    'token' => env('APPTRACKER_TOKEN'),

    // Phone app sync target, baked into the NativePHP build. The mobile
    // client is read-only over a private network, so the API host and its
    // bearer token ship with the bundle rather than being typed on-device.
    'mobile' => [
        'host' => env('APPTRACKER_SYNC_HOST'),
        'token' => env('APPTRACKER_TOKEN'),
    ],

    // Absolute path to the notes vault. attach_document stores vault-relative
    // paths and warns (does not fail) when a path does not resolve under this
    // directory. Unset means the check is skipped.
    'vault_path' => $vault,

    // Source-of-truth Markdown files that `tracker:import` parses into the DB.
    // The DB is a derived read model; these files stay hand-maintained. With no
    // env override and no vault, it falls back to the scrubbed sample files in
    // database/fixtures/ so the importer works on a fresh clone.
    'moc' => [
        'applications' => env('APPTRACKER_MOC_APPLICATIONS')
            ?: ($vault ? $vault.'/Projects/_Applications.md' : database_path('fixtures/_Applications.md')),
        'job_sort_queue' => env('APPTRACKER_MOC_JOB_SORT_QUEUE')
            ?: ($vault ? $vault.'/Projects/_Job Sort Queue.md' : database_path('fixtures/_Job Sort Queue.md')),
    ],

];
