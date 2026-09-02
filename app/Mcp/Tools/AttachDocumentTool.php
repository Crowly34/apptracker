<?php

namespace App\Mcp\Tools;

use App\Models\Application;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Record the vault-relative path to a frozen resume or cover letter. Warns (does not fail) when the path does not resolve under the configured vault.')]
class AttachDocumentTool extends Tool
{
    protected string $name = 'attach_document';

    private const COLUMNS = [
        'resume' => 'resume_path',
        'cover_letter' => 'cover_letter_path',
    ];

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'id' => ['required', 'integer'],
            'type' => ['required', Rule::in(array_keys(self::COLUMNS))],
            'path' => ['required', 'string', 'max:2048'],
        ]);

        $application = Application::find($validated['id']);

        if ($application === null) {
            return Response::error("No application with id {$validated['id']}.");
        }

        $application->{self::COLUMNS[$validated['type']]} = $validated['path'];
        $application->save();

        $payload = ['application' => $application, 'vault_path_ok' => true];

        if (! $this->resolvesUnderVault($validated['path'])) {
            $payload['vault_path_ok'] = false;
            $payload['warning'] = "Path does not resolve under the vault: {$validated['path']}";
        }

        return Response::json($payload);
    }

    private function resolvesUnderVault(string $path): bool
    {
        $vault = config('apptracker.vault_path');

        if (! is_string($vault) || $vault === '' || ! is_dir($vault)) {
            return true;
        }

        $resolved = realpath(rtrim($vault, '/').'/'.ltrim($path, '/'));

        return $resolved !== false && str_starts_with($resolved, realpath($vault));
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('The application id.')->required(),
            'type' => $schema->string()
                ->enum(array_keys(self::COLUMNS))
                ->description('Which document slot to write.')
                ->required(),
            'path' => $schema->string()
                ->description('Vault-relative path to the frozen file.')
                ->required(),
        ];
    }
}
