<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GeneratedTaskResource;
use App\Models\GeneratedTask;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Request;

class GeneratedTaskController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = GeneratedTask::query()->orderBy('sequence_no');

        if ($request->filled('request_id')) {
            $query->where('task_generation_request_id', $request->integer('request_id'));
        }

        return GeneratedTaskResource::collection($query->get());
    }
}
