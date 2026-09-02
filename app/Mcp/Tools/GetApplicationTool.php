<?php

namespace App\Mcp\Tools;

use App\Models\Application;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Fetch a single application row by id, with every field.')]
class GetApplicationTool extends Tool
{
    protected string $name = 'get_application';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'id' => ['required', 'integer'],
        ]);

        $application = Application::find($validated['id']);

        if ($application === null) {
            return Response::error("No application with id {$validated['id']}.");
        }

        return Response::json($application);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()
                ->description('The application id.')
                ->required(),
        ];
    }
}
