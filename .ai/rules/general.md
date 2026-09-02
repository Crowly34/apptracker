---
paths:
  - '**'
  - composer.json
---

# General

## Project scope: apptracker is a small demo — MCP + read-only NativePHP phone app
This repo is a deliberately small portfolio demo. Full scope, two parts only:

1. MCP server (laravel/mcp, local stdio, `php artisan mcp:start apptracker`) — Claude fills/annotates one `applications` table.
2. NativePHP for Mobile app in THIS repo (`composer require nativephp/mobile`, `native:install`, `native:run`) — a READ-ONLY phone view of the pipeline (Queue / Active / Closed + status + tier). Embedded PHP + in-container SQLite, syncs GET-only from the token-guarded REST API.

A small Laravel 13 + SQLite backend running locally (Herd, apptracker.test) is the source of truth; REST API guarded by a static `APPTRACKER_TOKEN` (no Sanctum).

Out of scope — do not build without asking: editing the pipeline from the phone (no PATCH-back / offline outbox), auth beyond the static token, funnel analytics / status_events, companies/contacts entities, job-board scraping, multi-user, public hosting. NativePHP paid plugins (Biometrics, Secure Storage, Push, Scanner, Geolocation) are not used.

## NativePHP mobile pins: dev-main + mobile-ui
The mobile app needs nativephp/mobile "dev-main as 4.9.9" (VCS repo NativePHP/mobile-air) plus nativephp/mobile-ui ^0.3.0. The published 4.3.x on Packagist ships the EDGE engine but NOT the SwiftUI/Compose content-element renderers, so native:text / badge / button / divider / inputs render blank on device — mobile-ui supplies them. minimum-stability must be "dev".

composer update against the mobile-air VCS repo needs a GitHub token: `composer config -g github-oauth.github.com "$(gh auth token)"` (otherwise "Could not authenticate against github.com").

After a version bump: `php artisan native:install ios -F`, then confirm `php artisan native:plugin:list` shows nativephp/mobile-ui (namespace NativeUI, iOS: Yes) and that Native\Mobile\UI\NativeUIServiceProvider::class is in App\Providers\NativeServiceProvider::plugins().
