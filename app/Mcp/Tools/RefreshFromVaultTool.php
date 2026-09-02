<?php

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Facades\Artisan;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Re-run tracker:import to rebuild the applications table from the hand-maintained Markdown files. Call this right after editing _Applications.md or _Job Sort Queue.md so the DB, API, and phone reflect the change. Pass dry_run to preview the counts without writing.')]
class RefreshFromVaultTool extends Tool
{
    protected string $name = 'refresh_from_vault';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'dry_run' => ['sometimes', 'boolean'],
        ]);

        $dryRun = $validated['dry_run'] ?? false;

        $exitCode = Artisan::call('tracker:import', $dryRun ? ['--dry-run' => true] : []);
        $output = trim(Artisan::output());

        if ($exitCode !== 0) {
            return Response::error("tracker:import failed.\n".$output);
        }

        return Response::json([
            'ok' => true,
            'dry_run' => $dryRun,
            'summary' => $output,
        ]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'dry_run' => $schema->boolean()
                ->description('Parse and report the counts without writing to the database.'),
        ];
    }
}
