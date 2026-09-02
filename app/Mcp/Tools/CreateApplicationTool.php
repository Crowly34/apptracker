<?php

namespace App\Mcp\Tools;

use App\Enums\TierEnum;
use App\Http\Requests\ApplicationRules;
use App\Models\Application;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Create an application at status=queued. Rejects an exact company+role duplicate unless force is true. Returns the new row.')]
class CreateApplicationTool extends Tool
{
    protected string $name = 'create_application';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'company' => ['required', ...ApplicationRules::for('company')],
            'role' => ['required', ...ApplicationRules::for('role')],
            'posting_url' => ['sometimes', 'nullable', ...ApplicationRules::for('posting_url')],
            'source' => ['sometimes', 'nullable', ...ApplicationRules::for('source')],
            'tier' => ['sometimes', 'nullable', ...ApplicationRules::for('tier')],
            'notes' => ['sometimes', 'nullable', ...ApplicationRules::for('notes')],
            'force' => ['sometimes', 'boolean'],
        ]);

        $force = $validated['force'] ?? false;
        unset($validated['force']);

        if (! $force) {
            $duplicate = Application::query()
                ->where('company', $validated['company'])
                ->where('role', $validated['role'])
                ->first();

            if ($duplicate !== null) {
                return Response::error(
                    "An application for {$validated['company']} — {$validated['role']} already exists "
                    ."(id {$duplicate->id}). Pass force=true to create it anyway."
                );
            }
        }

        $application = Application::create($validated);

        return Response::json($application);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'company' => $schema->string()->description('Hiring company.')->required(),
            'role' => $schema->string()->description('Role title.')->required(),
            'posting_url' => $schema->string()->format('uri')->description('Link to the posting.'),
            'source' => $schema->string()->description('Where it was found — board name, referral, cold lane.'),
            'tier' => $schema->string()->enum(TierEnum::values())->description('Fit tier from triage.'),
            'notes' => $schema->string()->description('Freeform notes.'),
            'force' => $schema->boolean()->description('Create even if an exact company+role row already exists.'),
        ];
    }
}
