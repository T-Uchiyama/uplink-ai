<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TaskGenerationRequestController;
use App\Http\Controllers\Api\GeneratedTaskController;

Route::prefix('task-generation-requests')->group(function () {
    Route::post('/', [TaskGenerationRequestController::class, 'store']);
    Route::get('/{id}', [TaskGenerationRequestController::class, 'show']);
});

Route::get('/generated-tasks', [GeneratedTaskController::class, 'index']);
