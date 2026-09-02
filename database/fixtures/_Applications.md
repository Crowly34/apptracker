# Applications

Scrubbed sample data with fictional companies. This is the bundled fallback for
`tracker:import` so the project runs without a notes vault. Point
`APPTRACKER_MOC_APPLICATIONS` (or `APPTRACKER_VAULT_PATH`) at your own file to
override it.

## Pipeline

| Company | Role | Applied | Channel | Status |
| --- | --- | --- | --- | --- |
| Meridian Freight | Senior Laravel Engineer | 2026-08-26 | https://example.com/meridian/senior-laravel | sent |
| Northwind Apps | Backend Engineer (PHP/Vue) | 2026-08-24 | https://jobs.example.com/northwind/backend | screening |
| Cobalt Systems | Staff Software Engineer, Backend | 2026-08-18 | referral | interview |
| Alpine Ledger | Platform Engineer | 2026-08-20 | https://example.com/alpine/platform | offer |
| Harbor & Finch | Product Engineer | 2026-08-12 | https://example.com/harbor-finch/product-eng | rejected |
| Tidewater Labs | Full-Stack Developer | 2026-08-04 | https://example.com/tidewater/fullstack | no response |
| Junction 12 | Senior PHP Engineer | 2026-07-28 | https://example.com/junction12/senior-php | withdrawn |

## Notes

Freeform notes live below the table; the importer stops at the first non-table line.
