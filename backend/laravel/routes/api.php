<?php

use App\Http\Controllers\Api\GeneratedTaskController;
use App\Http\Controllers\Api\TaskGenerationRequestController;
use Illuminate\Support\Facades\Route;

Route::post('/task-generation-requests', [TaskGenerationRequestController::class, 'store']);
Route::get('/task-generation-requests/{taskGenerationRequest}', [TaskGenerationRequestController::class, 'show']);
Route::get('/generated-tasks', [GeneratedTaskController::class, 'index']);
