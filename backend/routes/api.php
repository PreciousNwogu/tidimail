<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\InboxActionController;
use App\Http\Controllers\Api\MeController;
use App\Http\Controllers\Api\SenderController;
use App\Http\Controllers\Api\PushSubscriptionController;
use App\Http\Controllers\Api\SweepController;
use App\Http\Controllers\Auth\GoogleController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/logout', [GoogleController::class, 'logout'])->middleware('auth:sanctum');
Route::post('/auth/disconnect', [GoogleController::class, 'disconnect'])->middleware('auth:sanctum');
Route::delete('/me', [GoogleController::class, 'destroy'])->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', MeController::class);
    Route::get('/push/vapid', [PushSubscriptionController::class, 'vapid']);
    Route::post('/push/subscribe', [PushSubscriptionController::class, 'store']);

    Route::get('/accounts', [AccountController::class, 'index']);
    Route::post('/accounts/{account}/sync', [AccountController::class, 'sync']);

    Route::get('/sweep', [SweepController::class, 'show']);
    Route::post('/sweep/complete', [SweepController::class, 'complete']);
    Route::post('/sweep/apply-recommendations', [SweepController::class, 'applyRecommendations']);

    Route::get('/senders', [SenderController::class, 'index']);
    Route::get('/senders/pending-ids', [SenderController::class, 'pendingIds']);
    Route::post('/senders/review-bulk', [SenderController::class, 'reviewBulk']);
    Route::get('/senders/{sender}', [SenderController::class, 'show']);
    Route::post('/senders/{sender}/review', [SenderController::class, 'review']);

    Route::get('/actions', [InboxActionController::class, 'index']);
    Route::post('/actions/{action}/undo', [InboxActionController::class, 'undo']);
});
