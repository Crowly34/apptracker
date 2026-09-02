<?php

namespace App\Console\Commands;

use App\Models\Application;
use App\Support\Moc\ApplicationsTableParser;
use App\Support\Moc\JobSortQueueParser;
use App\Support\Moc\MocRow;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TrackerImport extends Command
{
    protected $signature = 'tracker:import
        {--applications= : Path to _Applications.md (defaults to config apptracker.moc.applications)}
        {--queue= : Path to _Job Sort Queue.md (defaults to config apptracker.moc.job_sort_queue)}
        {--dry-run : Parse and report without writing}';

    protected $description = 'Rebuild the applications table from the hand-maintained Markdown files.';

    public function handle(ApplicationsTableParser $applicationsParser, JobSortQueueParser $queueParser): int
    {
        $applicationsFile = $this->option('applications') ?: config('apptracker.moc.applications');
        $queueFile = $this->option('queue') ?: config('apptracker.moc.job_sort_queue');

        if (! is_string($applicationsFile) || ! is_file($applicationsFile)) {
            $this->error('Applications file not found: '.var_export($applicationsFile, true));

            return self::FAILURE;
        }

        $applications = $applicationsParser->parse((string) file_get_contents($applicationsFile));

        $queue = [];
        $dropped = 0;
        if (is_string($queueFile) && is_file($queueFile)) {
            foreach ($queueParser->parse((string) file_get_contents($queueFile)) as $row) {
                foreach ($applications as $application) {
                    if ($row->matches($application)) {
                        $dropped++;

                        continue 2;
                    }
                }
                $queue[] = $row;
            }
        } else {
            $this->warn('Job Sort Queue file not found, importing applications only.');
        }

        $this->line(sprintf(
            'Parsed %d applications, %d live queue rows (%d already in the applications file).',
            count($applications), count($queue), $dropped,
        ));

        if ($queue !== []) {
            $this->newLine();
            $this->line('Queue rows (not yet applied):');
            foreach ($queue as $row) {
                $this->line(sprintf('  %s · %s%s', $row->company, $row->role, $row->tier ? " [{$row->tier->value}]" : ''));
            }
        }

        DB::beginTransaction();

        $created = 0;
        $updated = 0;
        foreach ([...$applications, ...$queue] as $row) {
            $model = Application::firstOrNew(['company' => $row->company, 'role' => $row->role]);
            $model->fill($this->ownedAttributes($row));

            if (! $model->exists) {
                $created++;
            } elseif ($model->isDirty()) {
                $updated++;
            }

            $model->save();
        }

        $this->table(
            ['', 'Count'],
            [
                ['Created', $created],
                ['Updated', $updated],
                ['Unchanged', count($applications) + count($queue) - $created - $updated],
                ['Queue rows deduped', $dropped],
            ],
        );

        if ($this->option('dry-run')) {
            DB::rollBack();
            $this->comment('Dry run — rolled back.');

            return self::SUCCESS;
        }

        DB::commit();

        return self::SUCCESS;
    }

    /**
     * Status always applies; the optional columns are only written when the
     * file actually carries a value, so a re-import never nulls out a URL or
     * date that a later file edit merely dropped.
     *
     * @return array<string, mixed>
     */
    private function ownedAttributes(MocRow $row): array
    {
        return array_filter([
            'status' => $row->status,
            'applied_at' => $row->appliedAt,
            'tier' => $row->tier,
            'posting_url' => $row->postingUrl,
        ], fn ($value) => $value !== null);
    }
}
