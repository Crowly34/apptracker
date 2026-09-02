<?php

namespace Database\Seeders;

use App\Enums\StatusEnum;
use App\Enums\TierEnum;
use App\Models\Application;
use Illuminate\Database\Seeder;

/**
 * ~12 fictional rows spanning the full status range so the phone views
 * (Queue / Active / Closed) all have something to show. Companies match the
 * sample Markdown files in database/fixtures/.
 */
class ApplicationSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            // --- queued -----------------------------------------------------
            [
                'company' => 'Redcedar',
                'role' => 'Senior Backend Engineer',
                'posting_url' => 'https://example.com/redcedar/senior-backend',
                'source' => 'Ashby board',
                'status' => StatusEnum::Queued,
                'tier' => TierEnum::A,
                'notes' => 'Strong Laravel/queue fit.',
            ],
            [
                'company' => 'Paperkite',
                'role' => 'Laravel Developer, Backend-Focused',
                'posting_url' => 'https://example.com/paperkite/laravel',
                'source' => 'employer careers page',
                'status' => StatusEnum::Queued,
                'tier' => TierEnum::B,
            ],
            [
                'company' => 'Bellwether',
                'role' => 'Full Stack Engineer',
                'posting_url' => null,
                'source' => 'LinkedIn',
                'status' => StatusEnum::Queued,
                'tier' => TierEnum::C,
            ],
            [
                'company' => 'Nearshore Collective',
                'role' => 'PHP Full Stack Developer',
                'posting_url' => 'https://example.com/nearshore/php-fullstack',
                'source' => 'LinkedIn',
                'status' => StatusEnum::Queued,
                'tier' => TierEnum::C,
                'notes' => 'High-volume staffing shop — only if nothing better.',
            ],

            // --- applied, awaiting reply ----------------------------------
            [
                'company' => 'Payglass',
                'role' => 'Talent pool — general application (payment orchestration)',
                'posting_url' => 'https://example.com/payglass/talent-pool',
                'source' => 'Lever',
                'status' => StatusEnum::Applied,
                'tier' => TierEnum::B,
                'applied_at' => '2026-09-01',
                'notes' => 'Answered yes to prior payments-domain experience. Salary expectation given.',
                'resume_path' => 'resumes/sent/payglass-talent-pool.pdf',
            ],
            [
                'company' => 'Enroll360',
                'role' => 'Senior Backend Engineer',
                'posting_url' => 'https://example.com/enroll360/senior-backend',
                'source' => 'Ashby board',
                'status' => StatusEnum::Applied,
                'tier' => TierEnum::B,
                'applied_at' => '2026-08-31',
                'notes' => 'Waiting on their side; no follow-up yet.',
                'resume_path' => 'resumes/sent/enroll360-senior-backend.pdf',
            ],
            [
                'company' => 'Meridian Freight',
                'role' => 'Senior Laravel Engineer',
                'posting_url' => 'https://example.com/meridian/senior-laravel',
                'source' => 'employer careers page',
                'status' => StatusEnum::Applied,
                'tier' => TierEnum::B,
                'applied_at' => '2026-08-31',
                'next_action' => 'follow up if no reply',
                'next_action_due' => '2026-09-14',
                'resume_path' => 'resumes/sent/meridian-senior-laravel.pdf',
            ],

            // --- active mid-pipeline -------------------------------------
            [
                'company' => 'Ridgeline Strategic',
                'role' => 'Sr. Laravel Developer & Solutions Architect',
                'posting_url' => null,
                'source' => 'referral',
                'status' => StatusEnum::Screening,
                'tier' => TierEnum::A,
                'applied_at' => '2026-08-18',
                'next_action' => 'reply to scheduling email',
                'next_action_due' => '2026-09-03',
                'resume_path' => 'resumes/sent/ridgeline-sr-laravel.pdf',
                'cover_letter_path' => 'resumes/sent/ridgeline-cover.pdf',
            ],
            [
                'company' => 'Cobalt Systems',
                'role' => 'Staff Software Engineer, Backend (2nd loop)',
                'posting_url' => 'https://example.com/cobalt/staff-backend',
                'source' => 'employer careers page',
                'status' => StatusEnum::Interview,
                'tier' => TierEnum::A,
                'applied_at' => '2026-08-05',
                'next_action' => 'prep system-design round',
                'next_action_due' => '2026-09-08',
                'resume_path' => 'resumes/sent/cobalt-staff-backend.pdf',
            ],

            // --- closed --------------------------------------------------
            [
                'company' => 'Northgate Realty',
                'role' => 'Frontend SDE (R-40921)',
                'posting_url' => null,
                'source' => 'LinkedIn',
                'status' => StatusEnum::Rejected,
                'tier' => TierEnum::B,
                'applied_at' => '2026-08-04',
                'notes' => "\n[2026-08-11] Templated rejection.",
            ],
            [
                'company' => 'Tidewater Labs',
                'role' => 'Full-Stack Developer',
                'posting_url' => null,
                'source' => 'WeWorkRemotely',
                'status' => StatusEnum::Ghosted,
                'tier' => TierEnum::C,
                'applied_at' => '2026-07-22',
                'notes' => "\n[2026-08-15] No reply after ~3 weeks — marked ghosted.",
            ],
            [
                'company' => 'Junction 12',
                'role' => 'Senior PHP Engineer',
                'posting_url' => 'https://example.com/junction12/senior-php',
                'source' => 'cold lane',
                'status' => StatusEnum::Withdrawn,
                'tier' => TierEnum::C,
                'applied_at' => '2026-08-27',
                'notes' => "\n[2026-08-29] Withdrew — mid-level scope, pay too low.",
            ],
        ];

        foreach ($rows as $row) {
            Application::create($row);
        }
    }
}
