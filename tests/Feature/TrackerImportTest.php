<?php

namespace Tests\Feature;

use App\Enums\StatusEnum;
use App\Enums\TierEnum;
use App\Models\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\PendingCommand;
use Tests\TestCase;

class TrackerImportTest extends TestCase
{
    use RefreshDatabase;

    private string $applicationsFile;

    private string $queueFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->applicationsFile = tempnam(sys_get_temp_dir(), 'apps').'.md';
        $this->queueFile = tempnam(sys_get_temp_dir(), 'queue').'.md';

        file_put_contents($this->applicationsFile, <<<'MD'
        ## Pipeline

        | Company | Role | Applied | Posting age | Channel | Status |
        | --- | --- | --- | --- | --- | --- |
        | Vantek | Staff Software Engineer | 2026-08-26 | unknown | Ashby | sent |
        | Northgate Realty | Frontend SDE (R-40921) | 2026-07-21 | unknown | Form | rejected |

        ## Notes
        MD);

        file_put_contents($this->queueFile, <<<'MD'
        # Job Sort Queue

        ### Skylark, Back End Developer (2026-08-04)
        - **Tier:** T3 · **Bucket:** C · **Call:** send with the batch

        ### Vantek, Staff Software Engineer, Backend (2026-08-26)
        - **Tier:** T2 · **Bucket:** A · **Call:** send with the batch

        ### Prismix, Senior Engineer (2026-07-23)
        - **Tier:** DEAD · **Call:** skip
        MD);
    }

    protected function tearDown(): void
    {
        @unlink($this->applicationsFile);
        @unlink($this->queueFile);

        parent::tearDown();
    }

    private function import(array $options = []): PendingCommand
    {
        return $this->artisan('tracker:import', array_merge([
            '--applications' => $this->applicationsFile,
            '--queue' => $this->queueFile,
        ], $options));
    }

    public function test_it_imports_applications_with_mapped_status_and_date(): void
    {
        $this->import()->assertSuccessful();

        $row = Application::where('company', 'Vantek')->sole();
        $this->assertSame(StatusEnum::Applied, $row->status);
        $this->assertSame('2026-08-26', $row->applied_at->toDateString());

        $this->assertSame(StatusEnum::Rejected, Application::where('company', 'Northgate Realty')->sole()->status);
    }

    public function test_it_imports_a_live_queue_row_but_skips_dead_and_already_applied_rows(): void
    {
        $this->import()->assertSuccessful();

        $queued = Application::where('company', 'Skylark')->sole();
        $this->assertSame(StatusEnum::Queued, $queued->status);
        $this->assertSame(TierEnum::C, $queued->tier);

        // "Vantek" queue entry matches the sent row -> not duplicated
        $this->assertSame(1, Application::where('company', 'Vantek')->count());

        // "Prismix" is DEAD -> never imported
        $this->assertDatabaseMissing('applications', ['company' => 'Prismix']);
    }

    public function test_reimport_is_idempotent_and_preserves_columns_written_elsewhere(): void
    {
        $this->import()->assertSuccessful();

        Application::where('company', 'Vantek')->sole()->update([
            'notes' => 'recruiter call booked',
            'next_action' => 'send take-home',
        ]);

        $this->import()->assertSuccessful();

        $this->assertSame(3, Application::count());

        $row = Application::where('company', 'Vantek')->sole();
        $this->assertSame('recruiter call booked', $row->notes);
        $this->assertSame('send take-home', $row->next_action);
    }

    public function test_dry_run_writes_nothing(): void
    {
        $this->import(['--dry-run' => true])->assertSuccessful();

        $this->assertSame(0, Application::count());
    }

    public function test_it_imports_the_bundled_sample_mocs_when_no_paths_are_given(): void
    {
        $this->artisan('tracker:import')->assertSuccessful();

        $this->assertSame(StatusEnum::Interview, Application::where('company', 'Cobalt Systems')->sole()->status);
        $this->assertSame(StatusEnum::Ghosted, Application::where('company', 'Tidewater Labs')->sole()->status);

        $redcedar = Application::where('company', 'Redcedar')->sole();
        $this->assertSame(StatusEnum::Queued, $redcedar->status);
        $this->assertSame(TierEnum::A, $redcedar->tier);

        $this->assertDatabaseMissing('applications', ['company' => 'Old Harbor Co']);
    }

    public function test_it_fails_when_the_applications_file_is_missing(): void
    {
        $this->import(['--applications' => '/no/such/file.md'])->assertFailed();

        $this->assertSame(0, Application::count());
    }
}
