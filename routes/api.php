<?php

use App\Http\Controllers\Api\Auth\DoctorAuthController;
use App\Http\Controllers\Api\PatientController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post(
        'doctor/register',
        [DoctorAuthController::class, 'register']
    );

    Route::post(
        'doctor/login',
        [DoctorAuthController::class, 'login']
    );
});

Route::middleware(['auth:sanctum', 'doctor.tenant',])->prefix('doctor')->group(function () {
    Route::get('me', [DoctorAuthController::class, 'me']);
    Route::get('patients', [PatientController::class, 'index']);
    Route::post('patients',[PatientController::class, 'store']);
    Route::get('patients/{patient}',[PatientController::class, 'show']);
    Route::put('patients/{patientId}',[PatientController::class, 'update']);
    Route::delete('patients/{patientId}',[PatientController::class, 'destroy']);
    Route::post('logout',[DoctorAuthController::class, 'logout']);
});
