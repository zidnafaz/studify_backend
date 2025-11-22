<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\ClassScheduleController;
use App\Http\Controllers\CombinedScheduleController;
use App\Http\Controllers\PersonalScheduleController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// --- Health Check Endpoint (Bawaan Temanmu) ---
Route::get('health', function () {
    try {
        DB::connection()->getPdo();
        $dbStatus = 'connected';
    } catch (\Exception $e) {
        $dbStatus = 'disconnected';
    }

    return response()->json([
        'status' => 'ok',
        'timestamp' => now(),
        'database' => $dbStatus,
        'app' => config('app.name'),
        'environment' => config('app.env')
    ]);
});

// --- Public Routes (Bisa diakses tanpa login) ---
Route::post('users', [AuthController::class, 'register']);

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::delete('login', [AuthController::class, 'logout']);
    // Refresh endpoint - excluded from auth:api middleware so it can accept expired tokens
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::get('user', [AuthController::class, 'me']);
});

// --- Protected Routes (Hanya bisa diakses jika sudah login) ---
Route::middleware('auth:api')->group(function () {

    // Classroom Routes
    Route::apiResource('classrooms', ClassroomController::class);
    Route::post('classrooms/join', [ClassroomController::class, 'join']);
    Route::post('classrooms/{classroom}/leave', [ClassroomController::class, 'leave']);
    Route::post('classrooms/{classroom}/remove-member', [ClassroomController::class, 'removeMember']);
    Route::post('classrooms/{classroom}/transfer-ownership', [ClassroomController::class, 'transferOwnership']);

    // Nested resource routes for class schedules
    Route::apiResource('classrooms.schedules', ClassScheduleController::class)->parameters([
        'classrooms' => 'classroom',
        'schedules' => 'schedule'
    ]);

    Route::apiResource('personal-schedules', PersonalScheduleController::class);
});
