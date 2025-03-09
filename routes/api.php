<?php

use App\Http\Controllers\ConsultationController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\MedicalRecordController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\ReportController;
use App\Models\Consultation;
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
        Route::prefix('doctor')->middleware('doctor_or_patient:doctor')->group(function () {
            Route::post('/logout', [DoctorController::class, 'logout']);
            Route::get('/me', [DoctorController::class, 'currentUser']);
            Route::put('/profile', [DoctorController::class, 'updateProfile']);

            Route::get('/consultations', [ConsultationController::class, 'getConsultationBasedByDoctorId']);
            Route::get('/records', [MedicalRecordController::class, 'index']);
            Route::post('/records', [MedicalRecordController::class, 'store']);
            Route::get('/report', [ReportController::class, 'makeReport']);
            Route::get('/reports', [ReportController::class, 'index']);
        });

        Route::prefix('patient')->middleware('doctor_or_patient:patient')->group(function () {
            Route::post('/logout', [PatientController::class, 'logout']);
            Route::get('/me', [PatientController::class, 'currentUser']);
            Route::put('/profile', [PatientController::class, 'completeProfile']);

            Route::get('/doctor', [ConsultationController::class, 'index']);
            Route::post('/consultation', [ConsultationController::class, 'store']);
        });
    });
});
