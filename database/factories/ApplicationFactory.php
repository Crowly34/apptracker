<?php

namespace Database\Factories;

use App\Enums\StatusEnum;
use App\Enums\TierEnum;
use App\Models\Application;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Application>
 */
class ApplicationFactory extends Factory
{
    protected $model = Application::class;

    /**
     * A queued (not-yet-applied) row by default. Use the states below to move
     * a row further down the pipeline.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $company = fake()->unique()->company();
        $role = fake()->randomElement([
            'Senior Laravel Engineer',
            'Full-Stack Developer (PHP/Vue)',
            'Staff Software Engineer, Backend',
            'Senior PHP Engineer',
            'Product Engineer',
            'Senior Full Stack Platform Engineer',
        ]);

        return [
            'company' => $company,
            'role' => $role,
            'posting_url' => fake()->boolean(80) ? fake()->url() : null,
            'source' => fake()->randomElement([
                'LinkedIn', 'Ashby board', 'Lever', 'employer careers page',
                'referral', 'cold lane', 'WeWorkRemotely',
            ]),
            'status' => StatusEnum::Queued,
            'tier' => fake()->randomElement(TierEnum::cases()),
            'applied_at' => null,
            'next_action' => null,
            'next_action_due' => null,
            'notes' => fake()->boolean(40) ? fake()->sentence() : null,
            'resume_path' => null,
            'cover_letter_path' => null,
        ];
    }

    /** Explicitly queued. */
    public function queued(): static
    {
        return $this->state(fn () => [
            'status' => StatusEnum::Queued,
            'applied_at' => null,
        ]);
    }

    /** Applied but no reply yet. */
    public function applied(): static
    {
        return $this->state(fn () => [
            'status' => StatusEnum::Applied,
            'applied_at' => fake()->dateTimeBetween('-6 weeks', '-3 days'),
            'resume_path' => 'Resume/2026/sent/'.fake()->slug(2).'.pdf',
        ]);
    }

    /** Somewhere mid-pipeline (screening/interview/offer). */
    public function active(): static
    {
        return $this->state(fn () => [
            'status' => fake()->randomElement([
                StatusEnum::Screening, StatusEnum::Interview, StatusEnum::Offer,
            ]),
            'applied_at' => fake()->dateTimeBetween('-8 weeks', '-1 week'),
            'resume_path' => 'Resume/2026/sent/'.fake()->slug(2).'.pdf',
        ]);
    }

    /** Terminal state. */
    public function closed(): static
    {
        return $this->state(fn () => [
            'status' => fake()->randomElement([
                StatusEnum::Rejected, StatusEnum::Withdrawn, StatusEnum::Ghosted,
            ]),
            'applied_at' => fake()->dateTimeBetween('-12 weeks', '-3 weeks'),
        ]);
    }

    /** Attach a pending next action with a due date. */
    public function withNextAction(): static
    {
        return $this->state(fn () => [
            'next_action' => fake()->randomElement([
                'incognito resubmit', 'follow up with recruiter',
                'send take-home', 'reply to scheduling email', 'nudge referral',
            ]),
            'next_action_due' => fake()->dateTimeBetween('-1 week', '+2 weeks'),
        ]);
    }
}
