<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskGenerationRequest;
use App\Models\TaskGenerationRequest;
use App\Jobs\GenerateTasksJob;

class TaskGenerationRequestController extends Controller
{
    public function store(StoreTaskGenerationRequest $request)
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
        ]);

        GenerateTasksJob::dispatch($taskRequest->id);

        return response()->json([
            'id' => $taskRequest->id,
            'status' => 'accepted'
        ], 202);
    }

    public function show($id)
    {
        $request = TaskGenerationRequest::with('generatedTasks')
            ->findOrFail($id);

        return response()->json($request);
    }
}
