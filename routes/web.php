<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/invite/{code}', [\App\Http\Controllers\InviteController::class, 'index']);
Route::get('/download-app', function () {
    return view('download');
})->name('app.download');
