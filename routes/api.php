<?php

use App\Http\Controllers\AuthController;
// 1. Panggil Controller yang baru kita buat tadi
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
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::get('user', [AuthController::class, 'me']);
});

// --- PROTECTED ROUTES (Harus Login dulu baru bisa akses) ---
// Kita pakai 'auth:api' karena proyek ini pakai JWT
Route::middleware('auth:api')->group(function () {

    // INI BAGIAN TUGASMU:
    // Mendaftarkan rute CRUD untuk Personal Schedule
    // Otomatis membuat alamat: GET, POST, PUT, DELETE ke /personal-schedules
    Route::apiResource('personal-schedules', PersonalScheduleController::class);

});