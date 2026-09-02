<?php

namespace Tests\Feature;

use App\Enums\StatusEnum;
use App\Enums\TierEnum;
use App\Models\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ApplicationApiTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'test-token';

    protected function setUp(): void
    {
        parent::setUp();

        config(['apptracker.token' => self::TOKEN]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function apiJson(string $method, string $uri, array $data = [], bool $withToken = true): TestResponse
    {
        $headers = $withToken ? ['Authorization' => 'Bearer '.self::TOKEN] : [];

        return $this->json($method, $uri, $data, $headers);
    }

    public function test_requests_without_a_token_are_rejected(): void
    {
        Application::factory()->create();

        $this->apiJson('GET', '/api/applications', withToken: false)->assertUnauthorized();
    }

    public function test_requests_with_a_wrong_token_are_rejected(): void
    {
        $this->json('GET', '/api/applications', [], ['Authorization' => 'Bearer nope'])
            ->assertUnauthorized();
    }

    public function test_index_returns_all_rows_and_filters_by_status_tier_and_due_date(): void
    {
        Application::factory()->create(['status' => StatusEnum::Queued, 'tier' => TierEnum::A]);
        Application::factory()->create(['status' => StatusEnum::Applied, 'tier' => TierEnum::B]);
        Application::factory()->create([
            'status' => StatusEnum::Screening,
            'tier' => TierEnum::A,
            'next_action_due' => Carbon::parse('2026-09-05'),
        ]);

        $this->apiJson('GET', '/api/applications')->assertOk()->assertJsonCount(3);

        $this->apiJson('GET', '/api/applications?status=queued')
            ->assertOk()->assertJsonCount(1)
            ->assertJsonPath('0.status', 'queued');

        $this->apiJson('GET', '/api/applications?tier=A')->assertOk()->assertJsonCount(2);

        $this->apiJson('GET', '/api/applications?due_before=2026-09-10')
            ->assertOk()->assertJsonCount(1);

        $this->apiJson('GET', '/api/applications?due_before=2026-09-01')
            ->assertOk()->assertJsonCount(0);
    }

    public function test_it_creates_an_application_defaulting_to_queued(): void
    {
        $response = $this->apiJson('POST', '/api/applications', [
            'company' => 'Vantek',
            'role' => 'Staff Software Engineer, Backend',
            'posting_url' => 'https://jobs.ashbyhq.com/vantek',
            'tier' => 'A',
        ]);

        $response->assertCreated()
            ->assertJsonPath('company', 'Vantek')
            ->assertJsonPath('status', 'queued')
            ->assertJsonPath('tier', 'A')
            ->assertJsonPath('applied_at', null);

        $this->assertDatabaseHas('applications', ['company' => 'Vantek', 'status' => 'queued']);
    }

    public function test_it_rejects_a_create_missing_required_fields(): void
    {
        $this->apiJson('POST', '/api/applications', ['company' => 'Vantek'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('role');
    }

    public function test_it_rejects_an_invalid_status_enum(): void
    {
        $application = Application::factory()->create();

        $this->apiJson('PATCH', "/api/applications/{$application->id}", ['status' => 'pending'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }

    public function test_it_shows_and_deletes_a_row(): void
    {
        $application = Application::factory()->create();

        $this->apiJson('GET', "/api/applications/{$application->id}")
            ->assertOk()
            ->assertJsonPath('id', $application->id);

        $this->apiJson('DELETE', "/api/applications/{$application->id}")->assertNoContent();

        $this->assertDatabaseMissing('applications', ['id' => $application->id]);
    }

    public function test_leaving_queued_backfills_applied_at_with_today(): void
    {
        Carbon::setTestNow('2026-09-01 12:00:00');

        $application = Application::factory()->create([
            'status' => StatusEnum::Queued,
            'applied_at' => null,
        ]);

        $this->apiJson('PATCH', "/api/applications/{$application->id}", ['status' => 'applied'])
            ->assertOk()
            ->assertJsonPath('applied_at', '2026-09-01T00:00:00.000000Z');

        $this->assertSame('2026-09-01', $application->fresh()->applied_at->toDateString());
    }

    public function test_applied_at_is_not_overwritten_when_already_set(): void
    {
        $application = Application::factory()->create([
            'status' => StatusEnum::Applied,
            'applied_at' => Carbon::parse('2026-08-01'),
        ]);

        $this->apiJson('PATCH', "/api/applications/{$application->id}", ['status' => 'screening'])
            ->assertOk()
            ->assertJsonPath('applied_at', '2026-08-01T00:00:00.000000Z');
    }

    public function test_patch_can_clear_and_set_fields(): void
    {
        $application = Application::factory()->create(['next_action' => 'follow up']);

        $this->apiJson('PATCH', "/api/applications/{$application->id}", [
            'next_action' => null,
            'notes' => 'recruiter call booked',
        ])->assertOk()->assertJsonPath('next_action', null);

        $this->assertSame('recruiter call booked', $application->fresh()->notes);
    }
}
