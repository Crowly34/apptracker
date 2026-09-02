<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\MobileSetting;
use App\Services\SyncApplications;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SyncApplicationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'apptracker.mobile.host' => 'http://apptracker.example.test',
            'apptracker.mobile.token' => 'local-token',
        ]);
    }

    public function test_it_upserts_the_remote_applications_into_the_local_database(): void
    {
        Http::fake([
            'http://apptracker.example.test/api/applications' => Http::response([[
                'id' => 42,
                'company' => 'NativePHP',
                'role' => 'Mobile Engineer',
                'posting_url' => 'https://nativephp.com/jobs/42',
                'source' => 'Referral',
                'status' => 'interview',
                'tier' => 'A',
                'applied_at' => '2026-09-01',
                'next_action' => 'Prepare portfolio walkthrough',
                'next_action_due' => '2026-09-04',
                'notes' => 'Remote record',
                'resume_path' => null,
                'cover_letter_path' => null,
                'created_at' => '2026-09-01T00:00:00.000000Z',
                'updated_at' => '2026-09-02T00:00:00.000000Z',
            ]]),
        ]);

        $result = app(SyncApplications::class)->handle();

        $this->assertTrue($result->succeeded);
        $this->assertSame(1, $result->count);
        $this->assertDatabaseHas('applications', [
            'id' => 42,
            'company' => 'NativePHP',
            'status' => 'interview',
            'tier' => 'A',
        ]);
        $this->assertNotNull(MobileSetting::singleton()->last_synced_at);

        Http::assertSent(fn ($request): bool => $request->url() === 'http://apptracker.example.test/api/applications'
            && $request->hasHeader('Authorization', 'Bearer local-token'));
    }

    public function test_it_reports_a_missing_sync_target(): void
    {
        config(['apptracker.mobile.host' => null]);

        $result = app(SyncApplications::class)->handle();

        $this->assertFalse($result->succeeded);
        Http::assertNothingSent();
    }

    public function test_it_keeps_the_local_mirror_when_the_sync_cannot_connect(): void
    {
        Application::factory()->queued()->create(['company' => 'Existing row']);
        Http::fake(fn () => throw new ConnectionException('No connection'));

        $result = app(SyncApplications::class)->handle();

        $this->assertFalse($result->succeeded);
        $this->assertDatabaseHas('applications', ['company' => 'Existing row']);
    }
}
