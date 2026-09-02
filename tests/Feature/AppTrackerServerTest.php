<?php

namespace Tests\Feature;

use App\Enums\StatusEnum;
use App\Enums\TierEnum;
use App\Mcp\Servers\AppTrackerServer;
use App\Mcp\Tools\AttachDocumentTool;
use App\Mcp\Tools\CreateApplicationTool;
use App\Mcp\Tools\GetApplicationTool;
use App\Mcp\Tools\ListApplicationsTool;
use App\Mcp\Tools\RefreshFromVaultTool;
use App\Mcp\Tools\SetNextActionTool;
use App\Mcp\Tools\UpdateStatusTool;
use App\Models\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AppTrackerServerTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_applications_filters_by_status_tier_and_due_date(): void
    {
        Application::factory()->create(['company' => 'Queued Co', 'status' => StatusEnum::Queued, 'tier' => TierEnum::A]);
        Application::factory()->create(['company' => 'Applied Co', 'status' => StatusEnum::Applied, 'tier' => TierEnum::B]);
        Application::factory()->create([
            'company' => 'Screening Co',
            'status' => StatusEnum::Screening,
            'tier' => TierEnum::A,
            'next_action_due' => Carbon::parse('2026-09-05'),
        ]);

        AppTrackerServer::tool(ListApplicationsTool::class, [])
            ->assertOk()
            ->assertSee(['Queued Co', 'Applied Co', 'Screening Co']);

        AppTrackerServer::tool(ListApplicationsTool::class, ['status' => 'queued'])
            ->assertOk()
            ->assertSee('Queued Co')
            ->assertDontSee('Applied Co');

        AppTrackerServer::tool(ListApplicationsTool::class, ['tier' => 'A'])
            ->assertSee(['Queued Co', 'Screening Co'])
            ->assertDontSee('Applied Co');

        AppTrackerServer::tool(ListApplicationsTool::class, ['due_before' => '2026-09-10'])
            ->assertSee('Screening Co')
            ->assertDontSee('Queued Co');
    }

    public function test_list_applications_rejects_an_unknown_status(): void
    {
        AppTrackerServer::tool(ListApplicationsTool::class, ['status' => 'pending'])
            ->assertHasErrors();
    }

    public function test_get_application_returns_the_row_or_an_error(): void
    {
        $application = Application::factory()->create(['company' => 'Vantek']);

        AppTrackerServer::tool(GetApplicationTool::class, ['id' => $application->id])
            ->assertOk()
            ->assertSee('Vantek');

        AppTrackerServer::tool(GetApplicationTool::class, ['id' => 9999])
            ->assertHasErrors(['No application with id 9999.']);
    }

    public function test_get_application_requires_an_id(): void
    {
        AppTrackerServer::tool(GetApplicationTool::class, [])->assertHasErrors();
    }

    public function test_create_application_defaults_to_queued(): void
    {
        AppTrackerServer::tool(CreateApplicationTool::class, [
            'company' => 'Vantek',
            'role' => 'Staff Software Engineer',
            'tier' => 'A',
        ])->assertOk()->assertSee('"status":"queued"');

        $this->assertDatabaseHas('applications', [
            'company' => 'Vantek',
            'status' => 'queued',
            'tier' => 'A',
        ]);
    }

    public function test_create_application_guards_against_an_exact_duplicate(): void
    {
        Application::factory()->create(['company' => 'Vantek', 'role' => 'Staff Engineer']);

        AppTrackerServer::tool(CreateApplicationTool::class, [
            'company' => 'Vantek',
            'role' => 'Staff Engineer',
        ])->assertHasErrors();

        $this->assertSame(1, Application::where('company', 'Vantek')->count());

        AppTrackerServer::tool(CreateApplicationTool::class, [
            'company' => 'Vantek',
            'role' => 'Staff Engineer',
            'force' => true,
        ])->assertOk();

        $this->assertSame(2, Application::where('company', 'Vantek')->count());
    }

    public function test_update_status_backfills_applied_at_and_appends_a_dated_note(): void
    {
        Carbon::setTestNow('2026-09-01 12:00:00');

        $application = Application::factory()->create([
            'status' => StatusEnum::Queued,
            'applied_at' => null,
            'notes' => 'triaged A-tier',
        ]);

        AppTrackerServer::tool(UpdateStatusTool::class, [
            'id' => $application->id,
            'status' => 'applied',
            'note' => 'submitted via Ashby',
        ])->assertOk();

        $application->refresh();
        $this->assertSame('2026-09-01', $application->applied_at->toDateString());
        $this->assertSame("triaged A-tier\n[2026-09-01] submitted via Ashby", $application->notes);
    }

    public function test_update_status_rejects_an_unknown_status(): void
    {
        $application = Application::factory()->create();

        AppTrackerServer::tool(UpdateStatusTool::class, [
            'id' => $application->id,
            'status' => 'pending',
        ])->assertHasErrors();
    }

    public function test_set_next_action_sets_then_clears_both_fields(): void
    {
        $application = Application::factory()->create();

        AppTrackerServer::tool(SetNextActionTool::class, [
            'id' => $application->id,
            'action' => 'follow up with the recruiter',
            'due' => '2026-09-15',
        ])->assertOk();

        $application->refresh();
        $this->assertSame('follow up with the recruiter', $application->next_action);
        $this->assertSame('2026-09-15', $application->next_action_due->toDateString());

        AppTrackerServer::tool(SetNextActionTool::class, [
            'id' => $application->id,
            'action' => '',
        ])->assertOk();

        $application->refresh();
        $this->assertNull($application->next_action);
        $this->assertNull($application->next_action_due);
    }

    public function test_attach_document_writes_the_column_and_flags_an_off_vault_path(): void
    {
        config(['apptracker.vault_path' => null]);

        $application = Application::factory()->create();

        AppTrackerServer::tool(AttachDocumentTool::class, [
            'id' => $application->id,
            'type' => 'resume',
            'path' => 'resumes/vantek-staff.md',
        ])->assertOk()->assertSee('"vault_path_ok":true');

        $this->assertSame('resumes/vantek-staff.md', $application->fresh()->resume_path);

        config(['apptracker.vault_path' => base_path('tests')]);

        AppTrackerServer::tool(AttachDocumentTool::class, [
            'id' => $application->id,
            'type' => 'cover_letter',
            'path' => 'does/not/exist.md',
        ])->assertOk()->assertSee('"vault_path_ok":false');

        $this->assertSame('does/not/exist.md', $application->fresh()->cover_letter_path);
    }

    public function test_attach_document_rejects_an_unknown_type(): void
    {
        $application = Application::factory()->create();

        AppTrackerServer::tool(AttachDocumentTool::class, [
            'id' => $application->id,
            'type' => 'portfolio',
            'path' => 'x.md',
        ])->assertHasErrors();
    }

    public function test_refresh_from_vault_imports_the_configured_mocs(): void
    {
        $this->pointConfigAtTempMocs();

        AppTrackerServer::tool(RefreshFromVaultTool::class, [])
            ->assertOk()
            ->assertSee('"ok":true');

        $this->assertSame(StatusEnum::Applied, Application::where('company', 'Vantek')->sole()->status);
        $this->assertSame(StatusEnum::Queued, Application::where('company', 'Skylark')->sole()->status);
    }

    public function test_refresh_from_vault_dry_run_writes_nothing(): void
    {
        $this->pointConfigAtTempMocs();

        AppTrackerServer::tool(RefreshFromVaultTool::class, ['dry_run' => true])
            ->assertOk()
            ->assertSee('"dry_run":true');

        $this->assertSame(0, Application::count());
    }

    public function test_refresh_from_vault_errors_when_a_moc_is_missing(): void
    {
        config([
            'apptracker.moc.applications' => '/no/such/file.md',
            'apptracker.moc.job_sort_queue' => '/no/such/queue.md',
        ]);

        AppTrackerServer::tool(RefreshFromVaultTool::class, [])->assertHasErrors();

        $this->assertSame(0, Application::count());
    }

    private function pointConfigAtTempMocs(): void
    {
        $applications = tempnam(sys_get_temp_dir(), 'apps').'.md';
        $queue = tempnam(sys_get_temp_dir(), 'queue').'.md';

        file_put_contents($applications, <<<'MD'
        | Company | Role | Applied | Posting age | Channel | Status |
        | --- | --- | --- | --- | --- | --- |
        | Vantek | Staff Software Engineer | 2026-08-26 | unknown | Ashby | sent |
        MD);

        file_put_contents($queue, <<<'MD'
        ### Skylark, Back End Developer (2026-08-04)
        - **Tier:** T3 · **Bucket:** C · **Call:** send with the batch
        MD);

        config([
            'apptracker.moc.applications' => $applications,
            'apptracker.moc.job_sort_queue' => $queue,
        ]);

        $this->beforeApplicationDestroyed(function () use ($applications, $queue): void {
            @unlink($applications);
            @unlink($queue);
        });
    }
}
