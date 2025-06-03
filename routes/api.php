<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ConsultationController;
use App\Http\Controllers\FileUploadController;
use App\Http\Controllers\MedicineController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\MedicalRecordController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\ReportController;

Route::prefix('v1')->group(function () {
    // Authentication routes
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

    // Protected routes (require authentication)
    Route::middleware('auth:sanctum')->group(function () {
        // File Upload routes (accessible to all authenticated users)
        Route::post('/upload/document', [FileUploadController::class, 'uploadDocument']);

        // Admin routes (require admin role)
        Route::prefix('admin')->middleware('admin')->group(function () {
            // User management
            Route::get('/users', [AdminController::class, 'getAllUsers']);
            Route::put('/users/{id}/role', [AdminController::class, 'updateUserRole']);
            Route::delete('/users/{id}', [AdminController::class, 'deleteUser']);

            // Doctor application management
            Route::get('/doctor-applications', [AdminController::class, 'getDoctorApplications']);
            Route::put('/doctor-applications/{id}/approve', [AdminController::class, 'approveDoctorApplication']);
            Route::put('/doctor-applications/{id}/reject', [AdminController::class, 'rejectDoctorApplication']);

            // Medicine management
            Route::get('/medicines', [MedicineController::class, 'index']);
            Route::post('/medicines', [MedicineController::class, 'store']);
            Route::get('/medicines/{id}', [MedicineController::class, 'show']);
            Route::put('/medicines/{id}', [MedicineController::class, 'update']);
            Route::delete('/medicines/{id}', [MedicineController::class, 'destroy']);
            Route::put('/medicines/{id}/stock', [MedicineController::class, 'updateStock']);
        });

        // Doctor routes (require doctor role)
        Route::prefix('doctor')->middleware('doctor_or_patient:doctor')->group(function () {
            Route::post('/logout', [DoctorController::class, 'logout']);
            Route::get('/me', [DoctorController::class, 'currentUser']);
            Route::put('/profile', [DoctorController::class, 'updateProfile']);

            // Registration management
            Route::get('/registrations', [ConsultationController::class, 'getRegistrationsByDoctorId']);
            Route::put('/registrations/{id}/status', [ConsultationController::class, 'updateRegistrationStatus']);

            // Medical records management
            Route::get('/records', [MedicalRecordController::class, 'index']);
            Route::post('/records', [MedicalRecordController::class, 'store']);
            Route::get('/records/{id}', [MedicalRecordController::class, 'show']);

            // Medicine for prescribing
            Route::get('/medicines', [MedicineController::class, 'getAvailable']);

            // Reports
            Route::get('/report', [ReportController::class, 'makeReport']);
            Route::get('/reports', [ReportController::class, 'index']);
        });

        // Patient routes (require patient role)
        Route::prefix('patient')->middleware('doctor_or_patient:patient')->group(function () {
            Route::post('/logout', [PatientController::class, 'logout']);
            Route::get('/me', [PatientController::class, 'currentUser']);
            Route::put('/profile', [PatientController::class, 'completeProfile']);

            // Doctor list and registration
            Route::get('/doctors', [ConsultationController::class, 'index']);
            Route::post('/registration', [ConsultationController::class, 'store']);
            Route::get('/registrations', [ConsultationController::class, 'getRegistrationsByPatientId']);

            // Medical records access
            Route::get('/medical-records', [MedicalRecordController::class, 'getPatientRecords']);
        });
    });
});
