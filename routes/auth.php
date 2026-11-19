<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\PasswordResetController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.requestEmail');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
});

// Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.store');
// Route::post('/password/resend-otp', [PasswordResetController::class, 'resendOtp'])->name('password.resend-otp');
// Route::post('/password/reset', [PasswordResetController::class, 'resetPassword'])->name('password.reset');
// Route::get('reset-form', [PasswordResetController::class, 'showPasswordResetForm'])->name('password.reset.form');
// Route::post('/password/verify-otp', [PasswordResetController::class, 'verifyOtp'])->name('password.verify-otp');
// Route::post('/password/send-otp', [PasswordResetController::class, 'sendOtp'])->name('password.send-otp');
// Route::get('/password/otp', [PasswordResetController::class, 'showOtpForm'])->name('password.otp');
Route::get('/password/request', [PasswordResetController::class, 'showOtpForm'])->name('password.request');
Route::post('/password/send-otp', [PasswordResetController::class, 'sendOtp'])->name('password.send-otp');
Route::post('/password/verify-otp', [PasswordResetController::class, 'verifyAndShowPasswordResetForm'])->name('password.verify');
Route::get('/password/reset-form', [PasswordResetController::class, 'showPasswordResetForm'])->name('password.reset.form');
Route::post('/password/reset', [PasswordResetController::class, 'resetPassword'])->name('password.reset');
Route::post('/password/resend-otp', [PasswordResetController::class, 'resendOtp'])->name('password.resend');


Route::middleware('auth')->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

});
Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
    ->name('logout');
