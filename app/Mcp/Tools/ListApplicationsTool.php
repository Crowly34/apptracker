<?php

namespace App\Mcp\Tools;

use App\Enums\StatusEnum;
use App\Enums\TierEnum;
use App\Models\Application;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('List application rows, optionally filtered by status, tier, or an upcoming next-action due date. Returns compact fields; use get_application for the full row.')]
class ListApplicationsTool extends Tool
{
    protected string $name = 'list_applications';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'status' => ['sometimes', 'string', Rule::enum(StatusEnum::class)],
            'tier' => ['sometimes', 'string', Rule::enum(TierEnum::class)],
            'due_before' => ['sometimes', 'date'],
        ]);

        $applications = Application::query()
            ->when(
                isset($validated['status']),
                fn ($query) => $query->where('status', $validated['status']),
            )
            ->when(
                isset($validated['tier']),
                fn ($query) => $query->where('tier', $validated['tier']),
            )
            ->when(
                isset($validated['due_before']),
                fn ($query) => $query
                    ->whereNotNull('next_action_due')
                    ->whereDate('next_action_due', '<=', $validated['due_before']),
            )
            ->orderBy('created_at')
            ->get()
            ->map(fn (Application $application) => [
                'id' => $application->id,
                'company' => $application->company,
                'role' => $application->role,
                'status' => $application->status,
                'tier' => $application->tier,
                'next_action' => $application->next_action,
                'next_action_due' => $application->next_action_due?->toDateString(),
            ]);

        return Response::json($applications);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'status' => $schema->string()
                ->enum(StatusEnum::values())
                ->description('Only rows in this status.'),

            'tier' => $schema->string()
                ->enum(TierEnum::values())
                ->description('Only rows in this tier.'),

            'due_before' => $schema->string()
                ->format('date')
                ->description('Only rows with a next action due on or before this date (YYYY-MM-DD).'),
        ];
    }
}
