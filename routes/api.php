<?php

use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\Auth\DoctorAuthController;
use App\Http\Controllers\Api\ClinicalExtractionController;
use App\Http\Controllers\Api\ClinicController;
use App\Http\Controllers\Api\DoctorDashboardController;
use App\Http\Controllers\Api\InvestigationController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\PrescriptionController;
use App\Http\Controllers\Api\QueueController;
use App\Http\Controllers\Api\VisitController;
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
    Route::post('patients', [PatientController::class, 'store']);
    Route::get('patients/{patient}', [PatientController::class, 'show']);
    Route::put('patients/{patientId}', [PatientController::class, 'update']);
    Route::delete('patients/{patientId}', [PatientController::class, 'destroy']);
    Route::get('patients/{patientId}/clinical-history',[PatientController::class, 'clinicalHistory']);

    Route::get('clinics', [ClinicController::class, 'index']);
    Route::post('clinics', [ClinicController::class, 'store']);
    Route::get('clinics/{clinicId}', [ClinicController::class, 'show']);
    Route::put('clinics/{clinicId}', [ClinicController::class, 'update']);
    Route::patch('clinics/{clinicId}/status', [ClinicController::class, 'toggleStatus']);
    Route::delete('clinics/{clinicId}', [ClinicController::class, 'destroy']);

    Route::post('queues', [QueueController::class, 'store']);
    Route::get('queues', [QueueController::class, 'index']);
    Route::patch('queues/{queueId}/status', [QueueController::class, 'updateStatus']);
    Route::post('queues/call-next', [QueueController::class, 'callNext']);
    Route::get('queues/current', [QueueController::class, 'current']);
    Route::get('queues/summary', [QueueController::class, 'summary']);
    Route::get('queues/history', [QueueController::class, 'history']);

    Route::post('appointments', [AppointmentController::class, 'store']);
    Route::get('appointments', [AppointmentController::class, 'index']);
    Route::get('appointments/availability', [AppointmentController::class, 'availability']);
    Route::get('appointments/slots',[AppointmentController::class, 'slots']);
    Route::get('appointments/upcoming',[AppointmentController::class, 'upcoming']);
    Route::get('appointments/dashboard-summary',[AppointmentController::class, 'dashboardSummary']);
    Route::post('appointments/book', [AppointmentController::class, 'bookForPatient']);
    Route::patch('appointments/{appointmentId}/reschedule',[AppointmentController::class, 'reschedule']);
    Route::get('appointments/{appointmentId}', [AppointmentController::class, 'show']);
    Route::put('appointments/{appointmentId}', [AppointmentController::class, 'update']);
    Route::delete('appointments/{appointmentId}', [AppointmentController::class, 'destroy']);
    Route::patch('appointments/{appointmentId}/status', [AppointmentController::class, 'updateStatus']);
    Route::post('appointments/{appointmentId}/arrive', [AppointmentController::class, 'arrive']);


    Route::put('clinics/{clinicId}/working-hours',[ClinicController::class, 'updateWorkingHours']);
    Route::get('clinics/{clinicId}/working-hours',[ClinicController::class, 'workingHours']);
    Route::get('clinics/{clinicId}',[ClinicController::class, 'show']);

    Route::get('dashboard/summary',[DoctorDashboardController::class, 'summary']);

    Route::post('visits/start',[VisitController::class, 'start']);
    Route::post('visits/direct',[VisitController::class, 'direct']);//Emergency
    Route::patch('visits/{visitId}/complete',[VisitController::class, 'complete']);
    Route::get('visits/{visitId}',[VisitController::class, 'show']);
    Route::patch('visits/{visitId}',[VisitController::class, 'update']);


    Route::post('visits/{visitId}/clinical-extraction',[ClinicalExtractionController::class, 'store']);
    Route::get('visits/{visitId}/clinical-extraction',[ClinicalExtractionController::class, 'show']);
    Route::post('visits/{visitId}/clinical-extraction/confirm',[ClinicalExtractionController::class, 'confirm']);
    Route::post('visits/{visitId}/clinical-extraction/reject',[ClinicalExtractionController::class, 'reject']);

    Route::get('prescriptions/{prescriptionId}',[PrescriptionController::class, 'show']);
    Route::patch('prescriptions/{prescriptionId}',[PrescriptionController::class, 'update']);
    Route::delete('prescriptions/{prescriptionId}',[PrescriptionController::class, 'destroy']);
    Route::get('prescriptions',[PrescriptionController::class, 'index']);

    Route::get('investigations/{investigationId}',[InvestigationController::class, 'show']);
    Route::patch('investigations/{investigationId}',[InvestigationController::class, 'update']);
    Route::delete('investigations/{investigationId}',[InvestigationController::class, 'destroy']);
    Route::get('investigations',[InvestigationController::class, 'index']);



    Route::post('logout', [DoctorAuthController::class, 'logout']);
});
