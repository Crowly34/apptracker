<?php

namespace App\Services;

use App\Models\MobileSetting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncApplications
{
    /** @var list<string> */
    private const APPLICATION_COLUMNS = [
        'id', 'company', 'role', 'posting_url', 'source', 'status', 'tier',
        'applied_at', 'next_action', 'next_action_due', 'notes', 'resume_path',
        'cover_letter_path', 'created_at', 'updated_at',
    ];

    public function handle(): SyncResult
    {
        $host = rtrim((string) config('apptracker.mobile.host'), '/');
        $token = (string) config('apptracker.mobile.token');

        if ($host === '' || $token === '') {
            return SyncResult::failure('This build has no sync target configured.');
        }

        try {
            $response = Http::acceptJson()->withToken($token)->timeout(10)
                ->get("{$host}/api/applications");
        } catch (ConnectionException) {
            return SyncResult::failure('Could not reach the backend. Your saved applications are still available offline.');
        } catch (Throwable $exception) {
            Log::warning('Application sync failed before receiving a response.', ['exception' => $exception]);

            return SyncResult::failure('Could not start the sync. Your saved applications are still available offline.');
        }

        if (! $response->successful()) {
            return SyncResult::failure('The Mac rejected the sync. Check the host and token baked into this build.');
        }

        $payload = $response->json();

        if (! is_array($payload) || ! array_is_list($payload)) {
            return SyncResult::failure('The Mac returned an unexpected response.');
        }

        $applications = collect($payload)
            ->filter(fn (mixed $application): bool => is_array($application) && isset($application['id']))
            ->map(fn (array $application): array => Arr::only($application, self::APPLICATION_COLUMNS))
            ->values()
            ->all();

        if (count($applications) !== count($payload)) {
            return SyncResult::failure('The Mac returned an invalid application record.');
        }

        try {
            DB::transaction(function () use ($applications): void {
                if ($applications !== []) {
                    DB::table('applications')->upsert(
                        $applications,
                        ['id'],
                        array_values(array_diff(self::APPLICATION_COLUMNS, ['id', 'created_at'])),
                    );
                }

                MobileSetting::singleton()->markSynced();
            });
        } catch (Throwable $exception) {
            Log::warning('Application sync could not save the local mirror.', ['exception' => $exception]);

            return SyncResult::failure('Could not save the sync on this device.');
        }

        return SyncResult::success(count($applications));
    }
}
