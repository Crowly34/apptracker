<?php

namespace App\Mcp\Tools;

use App\Models\Application;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Set or clear the next action waiting on the candidate. An empty action clears both next_action and next_action_due.')]
class SetNextActionTool extends Tool
{
    protected string $name = 'set_next_action';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'id' => ['required', 'integer'],
            'action' => ['present', 'string'],
            'due' => ['sometimes', 'nullable', 'date'],
        ]);

        $application = Application::find($validated['id']);

        if ($application === null) {
            return Response::error("No application with id {$validated['id']}.");
        }

        $action = trim($validated['action']);

        if ($action === '') {
            $application->next_action = null;
            $application->next_action_due = null;
        } else {
            $application->next_action = $action;
            $application->next_action_due = $validated['due'] ?? null;
        }

        $application->save();

        return Response::json($application);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('The application id.')->required(),
            'action' => $schema->string()
                ->description('What is waiting on the candidate, e.g. "incognito resubmit". Empty string clears it.')
                ->required(),
            'due' => $schema->string()
                ->format('date')
                ->description('Due date (YYYY-MM-DD). Ignored when action is empty.'),
        ];
    }
}
