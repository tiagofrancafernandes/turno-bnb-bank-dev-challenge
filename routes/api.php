<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\NotificationController;

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

Route::any('ping/{message?}', fn (?string $message = null) => ['message' => $message ?? 'pong'])
    ->where('message', '[a-zA-Z0-9\-\ ]{0,}')
    ->name('ping');

Route::middleware('auth:sanctum')->get('/user', fn (Request $request) => $request->user());

Route::middleware('auth:sanctum')->group(function () {
    Route::match(['get', 'post'], 'notification', NotificationController::class)->name('notifications');
});
