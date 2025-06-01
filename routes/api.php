<?php

use App\Http\Controllers\AdminController;
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

        Route::prefix('admin')->group(function () {
            Route::post('/login', [AdminController::class, 'login']);
        });
    });

    Route::middleware('auth:sanctum')->group(function () {
        // Admin routes
        Route::prefix('admin')->middleware('admin')->group(function () {
            Route::get('/users', [AdminController::class, 'getAllUsers']);
            Route::put('/users/{id}/role', [AdminController::class, 'updateUserRole']);
            Route::delete('/users/{id}', [AdminController::class, 'deleteUser']);

            Route::get('/doctor-applications', [AdminController::class, 'getDoctorApplications']);
            Route::put('/doctor-applications/{id}/approve', [AdminController::class, 'approveDoctorApplication']);
            Route::put('/doctor-applications/{id}/reject', [AdminController::class, 'rejectDoctorApplication']);
        });

        // Doctor routes
        Route::prefix('doctor')->middleware('doctor_or_patient:doctor')->group(function () {
            Route::post('/logout', [DoctorController::class, 'logout']);
            Route::get('/me', [DoctorController::class, 'currentUser']);
            Route::put('/profile', [DoctorController::class, 'updateProfile']);

            Route::get('/registrations', [ConsultationController::class, 'getRegistrationsByDoctorId']);
            Route::get('/records', [MedicalRecordController::class, 'index']);
            Route::post('/records', [MedicalRecordController::class, 'store']);
            Route::get('/report', [ReportController::class, 'makeReport']);
            Route::get('/reports', [ReportController::class, 'index']);
        });

        // Patient routes
        Route::prefix('patient')->middleware('doctor_or_patient:patient')->group(function () {
            Route::post('/logout', [PatientController::class, 'logout']);
            Route::get('/me', [PatientController::class, 'currentUser']);
            Route::put('/profile', [PatientController::class, 'completeProfile']);

            Route::get('/doctors', [ConsultationController::class, 'index']);
            Route::post('/registration', [ConsultationController::class, 'store']);
            Route::get('/registrations', [ConsultationController::class, 'getRegistrationsByPatientId']);
        });
    });
});
