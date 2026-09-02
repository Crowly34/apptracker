<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\AttachDocumentTool;
use App\Mcp\Tools\CreateApplicationTool;
use App\Mcp\Tools\GetApplicationTool;
use App\Mcp\Tools\ListApplicationsTool;
use App\Mcp\Tools\RefreshFromVaultTool;
use App\Mcp\Tools\SetNextActionTool;
use App\Mcp\Tools\UpdateStatusTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Tool;

#[Name('AppTracker')]
#[Version('0.1.0')]
#[Instructions(<<<'TXT'
    Candidate-side job-application pipeline. One entity: applications, each with a
    status moving queued -> applied -> screening -> interview -> offer, or a closed
    state (rejected, withdrawn, ghosted). Feed it from job triage and
    tailored-resume output.

    - list_applications / get_application to read.
    - create_application drops a row in at status=queued.
    - update_status moves a row and can append a dated note.
    - set_next_action tracks what is waiting on the candidate.
    - attach_document records the frozen resume / cover-letter path.
    - refresh_from_vault re-runs tracker:import; call it after editing
      _Applications.md or _Job Sort Queue.md so the DB matches those files.

    The Markdown files are the source of truth for status / tier / applied_at /
    posting_url; refresh_from_vault pulls those in without touching next_action,
    notes, or the document paths. Deleting rows is out of scope here; do it in
    the app.
    TXT)]
class AppTrackerServer extends Server
{
    /**
     * @var array<int, class-string<Tool>>
     */
    protected array $tools = [
        ListApplicationsTool::class,
        GetApplicationTool::class,
        CreateApplicationTool::class,
        UpdateStatusTool::class,
        SetNextActionTool::class,
        AttachDocumentTool::class,
        RefreshFromVaultTool::class,
    ];

    /**
     * @var array<int, class-string<Server\Resource>>
     */
    protected array $resources = [];

    /**
     * @var array<int, class-string<Prompt>>
     */
    protected array $prompts = [];
}
