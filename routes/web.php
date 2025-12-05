<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/invite/{code}', [\App\Http\Controllers\InviteController::class, 'index']);
Route::get('/download-app', function () {
    return view('download');
})->name('app.download');

Route::get('/scheduler/run', function (\Illuminate\Http\Request $request) {
    $secret = env('CRON_SECRET');

    if (!$secret || $request->query('key') !== $secret) {
        abort(403, 'Unauthorized');
    }

    \Illuminate\Support\Facades\Artisan::call('schedule:run');

    return response()->json([
        'message' => 'Schedule run successfully',
        'output' => \Illuminate\Support\Facades\Artisan::output()
    ]);
});
