<?php

namespace App\Mcp\Tools;

use App\Enums\StatusEnum;
use App\Http\Requests\ApplicationRules;
use App\Models\Application;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Carbon;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Move an application to a new status. Back-fills applied_at the first time a row leaves queued. An optional note is appended to notes as a dated line.')]
class UpdateStatusTool extends Tool
{
    protected string $name = 'update_status';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'id' => ['required', 'integer'],
            'status' => ['required', ...ApplicationRules::for('status')],
            'note' => ['sometimes', 'nullable', ...ApplicationRules::for('notes')],
        ]);

        $application = Application::find($validated['id']);

        if ($application === null) {
            return Response::error("No application with id {$validated['id']}.");
        }

        $application->status = $validated['status'];

        if (! empty($validated['note'])) {
            $line = '['.Carbon::today()->toDateString().'] '.trim($validated['note']);
            $application->notes = $application->notes === null || $application->notes === ''
                ? $line
                : $application->notes."\n".$line;
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
            'status' => $schema->string()
                ->enum(StatusEnum::values())
                ->description('New status.')
                ->required(),
            'note' => $schema->string()
                ->description('Optional note, appended to notes as "[YYYY-MM-DD] <note>".'),
        ];
    }
}
