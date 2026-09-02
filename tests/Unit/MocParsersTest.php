<?php

namespace Tests\Unit;

use App\Enums\StatusEnum;
use App\Enums\TierEnum;
use App\Support\Moc\ApplicationsTableParser;
use App\Support\Moc\JobSortQueueParser;
use App\Support\Moc\MocRow;
use PHPUnit\Framework\TestCase;

class MocParsersTest extends TestCase
{
    private function applications(): string
    {
        return <<<'MD'
        # Applications

        ## Pipeline

        Some preamble prose that is not a table.

        | Company | Role | Applied | Posting age | Channel | Status | Days to close | Time (min) | Next action |
        | --- | --- | --- | --- | --- | --- | --- | --- | --- |
        | **Foghorn** (Dana) | Opportunistic reply | 2026-09-01 | n/a | Twitter/X DM, no link | sent | — | 10 | none |
        | Northgate Realty | Frontend SDE, Mexico (R-40921) | 2026-07-21 | unknown | Ashby (`jobs.ashbyhq.com/northgate/abc`) | rejected | 7 | — | closed |
        | Brightpath (agency), client = Halverson | Founding Engineer | 2026-08-24 | n/a | Lever form https://jobs.lever.co/brightpath/xyz here | acknowledged | — | — | wait |
        | Cloudnest | Software Engineer | n/a | unknown | Email | no response | — | — | none |
        | Somewhere | Full Stack, LATAM | 2026-08-06 | unknown | Form | | — | — | none |

        ## Drafted, not sent

        Prose after the table — parsing must stop here.
        MD;
    }

    public function test_applications_parser_extracts_and_normalizes_rows(): void
    {
        $rows = (new ApplicationsTableParser)->parse($this->applications());

        $this->assertCount(5, $rows);

        $this->assertSame('Foghorn', $rows[0]->company);
        $this->assertSame(StatusEnum::Applied, $rows[0]->status);
        $this->assertSame('2026-09-01', $rows[0]->appliedAt);
        $this->assertNull($rows[0]->postingUrl);

        $this->assertSame('Northgate Realty', $rows[1]->company);
        $this->assertSame(StatusEnum::Rejected, $rows[1]->status);

        // company cell keeps only the name before "(" and ","
        $this->assertSame('Brightpath', $rows[2]->company);
        $this->assertSame(StatusEnum::Screening, $rows[2]->status);
        $this->assertSame('https://jobs.lever.co/brightpath/xyz', $rows[2]->postingUrl);

        // "no response" -> ghosted; missing date -> null
        $this->assertSame(StatusEnum::Ghosted, $rows[3]->status);
        $this->assertNull($rows[3]->appliedAt);

        // empty status on a sent row falls back to applied
        $this->assertSame(StatusEnum::Applied, $rows[4]->status);
    }

    public function test_applications_parser_ignores_a_schemeless_url_in_the_channel_cell(): void
    {
        $rows = (new ApplicationsTableParser)->parse($this->applications());

        // Northgate Realty's channel has only `jobs.ashbyhq.com/…` in backticks, no scheme.
        $this->assertNull($rows[1]->postingUrl);
    }

    private function queue(): string
    {
        return <<<'MD'
        # Job Sort Queue

        ## Market counters

        | Signal | Count | Assessed |
        | --- | --- | --- |
        | AI tooling | 20 | 74 |

        ### Skylark, Back End Developer (2026-08-04)
        - **Tier:** T3 · **Bucket:** C · **Call:** send with the batch, ~30 minutes

        ### Prismix (prismix.ai), Senior Software Engineer PHP/Laravel (2026-07-23)
        - **Tier:** DEAD · **Call:** skip. US citizenship required

        ### SendKite, PHP Engineer (2026-07-23)
        - **Tier:** T4 · **Call:** do not send. Bottom of the queue

        ### Meridian Logic (Hartwell Group), PHP Developer (2026-07-27)
        - **Tier:** T1 · **Bucket:** C · **Call:** SENT 2026-07-27, baseline resume

        ### GAT (Gestión Automotriz del Trópico, S.A. de C.V.), Líder Técnico Laravel (2026-08-17)
        - **Tier:** T1 · **Bucket:** B · **Call:** hold until 2026-09-15, do not send
        MD;
    }

    public function test_queue_parser_returns_only_live_rows(): void
    {
        $rows = (new JobSortQueueParser)->parse($this->queue());

        // Skylark is live; Prismix (DEAD), SendKite ("do not send"),
        // Meridian Logic ("SENT"), GAT ("do not send") are all dropped.
        $this->assertCount(1, $rows);
        $this->assertSame('Skylark', $rows[0]->company);
        $this->assertSame('Back End Developer', $rows[0]->role);
        $this->assertSame(StatusEnum::Queued, $rows[0]->status);
        $this->assertSame(TierEnum::C, $rows[0]->tier);
    }

    public function test_queue_parser_splits_a_heading_with_commas_inside_parentheses(): void
    {
        $md = <<<'MD'
        ### GAT (Gestión Automotriz del Trópico, S.A. de C.V.), Líder Técnico Laravel (2026-08-17)
        - **Tier:** T2 · **Bucket:** A · **Call:** send now
        MD;

        $rows = (new JobSortQueueParser)->parse($md);

        $this->assertCount(1, $rows);
        $this->assertSame('GAT (Gestión Automotriz del Trópico, S.A. de C.V.)', $rows[0]->company);
        $this->assertSame('Líder Técnico Laravel', $rows[0]->role);
        $this->assertSame(TierEnum::A, $rows[0]->tier);
    }

    public function test_moc_row_matches_across_punctuation_and_trailing_ids(): void
    {
        $queue = new MocRow('Meridian Logic (Hartwell Group)', 'Senior Backend Developer (PHP / MariaDB) IRC301765', StatusEnum::Queued);
        $sent = new MocRow('Meridian Logic', 'Senior Backend Developer (PHP / MariaDB)', StatusEnum::Applied);
        $unrelated = new MocRow('Meridian Logic', 'PHP Developer', StatusEnum::Applied);

        $this->assertTrue($queue->matches($sent));
        $this->assertFalse($queue->matches($unrelated));
    }
}
