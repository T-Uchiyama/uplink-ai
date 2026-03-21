<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GeneratedTask;

class GeneratedTaskController extends Controller
{
    public function index(Request $request)
    {
        $requestId = $request->query('request_id');

        $tasks = GeneratedTask::where('task_generation_request_id', $requestId)
            ->orderBy('sequence_no')
            ->get();

        return response()->json($tasks);
    }
}
