# AppTracker

A small candidate-side job-application pipeline. One `applications` table whose
rows move through `queued → applied → screening → interview → offer` (or a closed
state: `rejected`, `withdrawn`, `ghosted`).

It has three parts:

- **Laravel 13 + SQLite backend** — the source of truth, served locally by Herd
  at `apptracker.test`. Rows are rebuilt from hand-maintained Markdown notes via
  `php artisan tracker:import`.
- **MCP server** (`laravel/mcp`, local stdio) — lets Claude read and annotate the
  pipeline: list/get applications, create a row, move status, set the next
  action, attach a resume path, or re-import from the Markdown files.
- **NativePHP for Mobile app** — a read-only phone view of the pipeline
  (Queue / Active / Closed, plus status and tier). It bundles embedded PHP and an
  in-container SQLite DB, and syncs GET-only from the token-guarded REST API.

## Why

Did this application while trying a couple of things: (1) to show that I can
commit to learning cross-tech really quickly, and to show my thinking process;
(2) to learn NativePHP, which I've been really interested in; and (3) since I'm
already keeping track of my job applications and their status, I might as well
build something that works for me.

## Screenshots

<img width="280" height="609" alt="AppTracker phone app — pipeline view" src="https://github.com/user-attachments/assets/c557998c-e285-462b-95a7-fcc687121b20" />


## Setup

Requires PHP 8.5, Composer, Node, and (for local serving) [Laravel Herd](https://herd.laravel.com/).

```bash
composer setup                  # install, .env, key:generate, migrate, npm install, npm build
php artisan migrate --seed      # ~12 sample applications across the full status range
```

Then set `APPTRACKER_TOKEN` in `.env` (the static bearer token for every `/api`
route) and you have a working pipeline.

The seed data (`database/seeders/ApplicationSeeder.php`, backed by
`ApplicationFactory`) is self-contained.

### Importing from Markdown

The pipeline can also be rebuilt from two Markdown files:

```bash
php artisan tracker:import          # add --dry-run to preview
```

With no configuration it reads the bundled sample files in `database/fixtures/`
(`_Applications.md` is a pipe table, `_Job Sort Queue.md` is triage entries), so
the command works on a fresh clone. Point it at your own files with:

```dotenv
APPTRACKER_VAULT_PATH=              # reads <vault>/Projects/_Applications.md + _Job Sort Queue.md
# or per-file:
APPTRACKER_MOC_APPLICATIONS=
APPTRACKER_MOC_JOB_SORT_QUEUE=
```

or `--applications=` / `--queue=` on the command.

### Mobile sync host

```dotenv
APPTRACKER_SYNC_HOST=        # private-network host the phone app syncs from, baked into the build
```

## Tests

```bash
php artisan test
```
