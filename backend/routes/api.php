<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClassController;
use App\Http\Controllers\Api\FingerprintController;
use App\Http\Controllers\Api\LockController;
use App\Http\Controllers\Api\MemberController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PasswordResetController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

Route::post('/forgot-password', [PasswordResetController::class, 'sendOtp'])->middleware('throttle:3,1');
Route::post('/reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:6,1');

Route::middleware(['auth:sanctum', 'gym.active'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/member', function (Request $request) {
        return $request->user();
    });

    Route::get('/member/qr', [MemberController::class, 'qr']);
    Route::get('/member/membership', [MemberController::class, 'membership']);
    Route::post('/member/fcm-token', [MemberController::class, 'updateFcmToken']);
    Route::get('/member/attendance', [MemberController::class, 'attendance']);
    Route::get('/member/measurements', [MemberController::class, 'measurements']);
    Route::post('/member/measurements', [MemberController::class, 'storeMeasurement']);
    Route::put('/member/profile', [MemberController::class, 'updateProfile']);
    Route::get('/member/payments', [MemberController::class, 'payments']);
    Route::post('/member/photo', [MemberController::class, 'updatePhoto']);
    Route::post('/member/change-password', [MemberController::class, 'changePassword']);

    Route::get('/member/notifications', [NotificationController::class, 'index']);
    Route::post('/member/notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::delete('/member/notifications/{id}', [NotificationController::class, 'destroy']);
    Route::delete('/member/notifications', [NotificationController::class, 'destroyAll']);

    Route::get('/classes', [ClassController::class, 'index']);
    Route::post('/classes/{class}/book', [ClassController::class, 'book']);
    Route::post('/bookings/{booking}/cancel', [ClassController::class, 'cancel']);

    Route::middleware('gym.hardware')->group(function () {
        Route::get('/lock/devices', [LockController::class, 'devices']);
        Route::post('/lock/devices/{device}/unlock', [LockController::class, 'unlock']);
        Route::post('/lock/devices/{device}/lock', [LockController::class, 'lock']);
        Route::get('/lock/commands/{command}/status', [LockController::class, 'commandStatus']);
    });
});

// Device routes: use a per-device token header instead of normal login,
// since the ESP32 has no user session.
Route::middleware('throttle:120,1')->group(function () {
    Route::get('/lock/poll', [LockController::class, 'pollCommands']);
    Route::post('/lock/commands/{command}/ack', [LockController::class, 'ackCommand']);
    Route::post('/lock/commands/{command}/progress', [LockController::class, 'progressCommand']);
    Route::post('/fingerprint/scan', [FingerprintController::class, 'scan']);
});
