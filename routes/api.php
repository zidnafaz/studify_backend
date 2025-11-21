<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\ClassScheduleController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Health Check Endpoint
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

// Authentication Routes
Route::post('users', [AuthController::class, 'register']); // RESTful: POST /api/users for creating user

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::delete('login', [AuthController::class, 'logout']); // RESTful: DELETE to remove session
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::get('user', [AuthController::class, 'me']); // RESTful: GET /api/auth/user for current user
});

// Class Schedule Routes - Protected by JWT
// RESTful API for nested resource: /classrooms/{classroom}/schedules
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
});
