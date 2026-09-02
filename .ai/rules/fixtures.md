---
paths:
  - '{database/seeders/**,database/factories/**,database/fixtures/**,tests/**}'
---

# Fixtures

## Seed/test/fixture data is fictional — never real pipeline data
The repo is a public portfolio demo. ApplicationSeeder, the fixture MoCs in database/fixtures/, and all test fixtures use invented companies (Meridian Freight, Cobalt Systems, Redcedar, Skylark, Prismix, …) and invented notes. Never paste in real companies, real posting URLs, real recruiter names, or notes from the maintainer's actual job search. tracker:import falls back to database/fixtures/ when APPTRACKER_VAULT_PATH / APPTRACKER_MOC_* are unset, so the command works on a fresh clone without a vault.
