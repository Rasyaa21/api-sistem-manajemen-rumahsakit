<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\PatientController;
use App\Models\Doctor;
use App\Models\Patient;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::prefix('doctor')->group(function () {
            Route::post('/register', [DoctorController::class, 'register']);
            Route::post('/login', [DoctorController::class, 'login']);
        });

        Route::prefix('patient')->group(function () {
            Route::post('/register', [PatientController::class, 'register']);
            Route::post('/login', [PatientController::class, 'login']);
        });
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::prefix('doctor')->middleware('role:' . Doctor::class)->group(function () {
            Route::post('/logout', [DoctorController::class, 'logout']);
            Route::get('/me', [DoctorController::class, 'currentUser']);
            Route::put('/profile', [DoctorController::class, 'updateProfile']);
        });

        Route::prefix('patient')->middleware('role:' . Patient::class)->group(function () {
            Route::post('/logout', [PatientController::class, 'logout']);
            Route::get('/me', [PatientController::class, 'currentUser']);
            Route::put('/profile', [PatientController::class, 'completeProfile']);
        });
    });
});
