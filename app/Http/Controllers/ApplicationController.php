<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreApplicationRequest;
use App\Http\Requests\UpdateApplicationRequest;
use App\Models\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Models are returned without an API Resource: the model casts serialize enums
 * to their backing strings and dates to ISO 8601, which is the shape the client
 * expects.
 */
class ApplicationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $applications = Application::query()
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->query('status')))
            ->when($request->filled('tier'), fn ($query) => $query->where('tier', $request->query('tier')))
            ->when(
                $request->filled('due_before'),
                fn ($query) => $query
                    ->whereNotNull('next_action_due')
                    ->whereDate('next_action_due', '<=', $request->date('due_before')),
            )
            ->orderBy('created_at')
            ->get();

        return response()->json($applications);
    }

    public function store(StoreApplicationRequest $request): JsonResponse
    {
        $application = Application::create($request->validated());

        return response()->json($application, Response::HTTP_CREATED);
    }

    public function show(Application $application): JsonResponse
    {
        return response()->json($application);
    }

    /**
     * A status change here can back-fill `applied_at` — that rule lives on the
     * Application model, not this method.
     */
    public function update(UpdateApplicationRequest $request, Application $application): JsonResponse
    {
        $application->update($request->validated());

        return response()->json($application);
    }

    public function destroy(Application $application): Response
    {
        $application->delete();

        return response()->noContent();
    }
}
