<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskGenerationRequest;
use App\Http\Resources\TaskGenerationRequestResource;
use App\Jobs\GenerateTasksJob;
use App\Models\TaskGenerationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskGenerationRequestController extends Controller
{
    public function store(StoreTaskGenerationRequest $request): JsonResponse
    {
        $data = $request->validated();

        $taskRequest = TaskGenerationRequest::create([
            'member_id' => $data['member_id'],
            'goal' => $data['goal'],
            'available_hours' => $data['available_hours'],
            'previous_score' => $data['previous_score'] ?? null,
            'note' => $data['note'] ?? null,
            'status' => 'pending',
            'generation_version' => 1,
            'input_snapshot' => null,
        ]);

        GenerateTasksJob::dispatch($taskRequest->id);

        return response()->json([
            'id' => $taskRequest->id,
            'status' => $taskRequest->status,
            'message' => 'Task generation request has been accepted.',
        ], 202);
    }

    public function show(Request $request, TaskGenerationRequest $taskGenerationRequest): TaskGenerationRequestResource
    {
        $taskGenerationRequest->load([
            'generatedTasks' => fn ($q) => $q->orderBy('sequence_no'),
            'aiExecutionLogs' => fn ($q) => $q->latest('executed_at'),
        ]);

        return new TaskGenerationRequestResource($taskGenerationRequest);
    }
}
