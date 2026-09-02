<?php

use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\Auth\DoctorAuthController;
use App\Http\Controllers\Api\Auth\MasterAuthController;
use App\Http\Controllers\Api\Auth\MedicineOrganizationAuthController;
use App\Http\Controllers\Api\ClinicalExtractionController;
use App\Http\Controllers\Api\ClinicController;
use App\Http\Controllers\Api\DoctorDashboardController;
use App\Http\Controllers\Api\DoctorHandwritingCorrectionController;
use App\Http\Controllers\Api\DoctorHandwritingSampleController;
use App\Http\Controllers\Api\DoctorMedicineLibraryController;
use App\Http\Controllers\Api\DoctorMedicineOrganizationController;
use App\Http\Controllers\Api\GlobalMedicineLibraryController;
use App\Http\Controllers\Api\InvestigationController;
use App\Http\Controllers\Api\MedicineOrganizationController;
use App\Http\Controllers\Api\MedicineOrganizationSubscriptionController;
use App\Http\Controllers\Api\OrganizationMedicineController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\PrescriptionController;
use App\Http\Controllers\Api\QueueController;
use App\Http\Controllers\Api\VisitController;
use App\Http\Controllers\Api\WebLoginController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('web-login/challenge', [WebLoginController::class, 'challenge']);

    Route::post('doctor/register', [DoctorAuthController::class, 'register']); //Android & iOS App
    Route::post('doctor/login', [DoctorAuthController::class, 'login']); //Android & iOS App


    Route::post('medicine-organization/register', [MedicineOrganizationAuthController::class, 'register']); //Angular Web App
    Route::post('medicine-organization/login', [MedicineOrganizationAuthController::class, 'login']); //Angular Web App

    Route::post('master/login', [MasterAuthController::class, 'login']); //Angular Web App
});

//Medicine organization APIs
Route::middleware([
    'auth:sanctum',
    'medicine_organizations',
])->prefix('med')->group(function () {
    Route::get('me', [MedicineOrganizationAuthController::class, 'me']);
    Route::post('logout', [MedicineOrganizationAuthController::class, 'logout']);
    Route::get('subscription-requests', [MedicineOrganizationSubscriptionController::class, 'index']);
    Route::patch('subscription-requests/{subscriptionId}', [MedicineOrganizationSubscriptionController::class, 'update']);
});

// Master database APIs
Route::middleware([
    'auth:sanctum',
    'master_admins',
])->prefix('master')->group(function () {

    Route::get('medicine-organizations', [MedicineOrganizationController::class, 'index']);
    Route::post('medicine-organizations', [MedicineOrganizationController::class, 'store']);
    Route::get('medicine-organizations/{organizationId}', [MedicineOrganizationController::class, 'show']);
    Route::patch('medicine-organizations/{organizationId}', [MedicineOrganizationController::class, 'update']);
    Route::delete('medicine-organizations/{organizationId}', [MedicineOrganizationController::class, 'destroy']);

    Route::get('medicine-organizations/{organizationId}/medicines', [OrganizationMedicineController::class, 'index']);
    Route::post('medicine-organizations/{organizationId}/medicines', [OrganizationMedicineController::class, 'store']);
    Route::get('medicine-organizations/{organizationId}/medicines/{medicineId}', [OrganizationMedicineController::class, 'show']);
    Route::patch('medicine-organizations/{organizationId}/medicines/{medicineId}', [OrganizationMedicineController::class, 'update']);
    Route::delete('medicine-organizations/{organizationId}/medicines/{medicineId}', [OrganizationMedicineController::class, 'destroy']);


    Route::get('global-medicines', [GlobalMedicineLibraryController::class, 'index']);
    Route::post('global-medicines', [GlobalMedicineLibraryController::class, 'store']);
    Route::get('global-medicines/{medicineId}', [GlobalMedicineLibraryController::class, 'show']);
    Route::patch('global-medicines/{medicineId}', [GlobalMedicineLibraryController::class, 'update']);
    Route::delete('global-medicines/{medicineId}', [GlobalMedicineLibraryController::class, 'destroy']);
});


// Authenticated Android doctor
Route::post('auth/web-login/approve', [WebLoginController::class, 'approve'])->middleware('auth:sanctum');

Route::post(
    'auth/web-login/complete',
    [WebLoginController::class, 'complete']
)->middleware('web.login.session');


//// Doctor-tenant APIs
Route::middleware([
    'auth:sanctum',
    'doctor.tenant'
])->prefix('doctor')->group(function () {


    Route::get('me', [DoctorAuthController::class, 'me']);
    Route::patch('me', [DoctorAuthController::class, 'updateProfile']);
    Route::patch('password', [DoctorAuthController::class, 'changePassword']);

    Route::get('handwriting-samples', [DoctorHandwritingSampleController::class, 'index']);
    Route::get('handwriting-samples/file', [DoctorHandwritingSampleController::class, 'file']);
    Route::post('handwriting-samples', [DoctorHandwritingSampleController::class, 'store']);
    Route::put('handwriting-samples', [DoctorHandwritingSampleController::class, 'update']);

    Route::post('handwriting/correct', [DoctorHandwritingCorrectionController::class, 'correct']);

    Route::get('patients', [PatientController::class, 'index']);
    Route::post('patients', [PatientController::class, 'store']);
    Route::get('patients/{patient}', [PatientController::class, 'show']);
    Route::put('patients/{patientId}', [PatientController::class, 'update']);
    Route::delete('patients/{patientId}', [PatientController::class, 'destroy']);
    Route::get('patients/{patientId}/clinical-history', [PatientController::class, 'clinicalHistory']);

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
    Route::get('appointments/slots', [AppointmentController::class, 'slots']);
    Route::get('appointments/upcoming', [AppointmentController::class, 'upcoming']);
    Route::get('appointments/dashboard-summary', [AppointmentController::class, 'dashboardSummary']);
    Route::post('appointments/book', [AppointmentController::class, 'bookForPatient']);
    Route::patch('appointments/{appointmentId}/reschedule', [AppointmentController::class, 'reschedule']);
    Route::get('appointments/{appointmentId}', [AppointmentController::class, 'show']);
    Route::put('appointments/{appointmentId}', [AppointmentController::class, 'update']);
    Route::delete('appointments/{appointmentId}', [AppointmentController::class, 'destroy']);
    Route::patch('appointments/{appointmentId}/status', [AppointmentController::class, 'updateStatus']);
    Route::post('appointments/{appointmentId}/arrive', [AppointmentController::class, 'arrive']);


    Route::put('clinics/{clinicId}/working-hours', [ClinicController::class, 'updateWorkingHours']);
    Route::get('clinics/{clinicId}/working-hours', [ClinicController::class, 'workingHours']);
    Route::get('clinics/{clinicId}', [ClinicController::class, 'show']);

    Route::get('dashboard/summary', [DoctorDashboardController::class, 'summary']);

    Route::post('visits/start', [VisitController::class, 'start']);
    Route::post('visits/direct', [VisitController::class, 'direct']); //Emergency
    Route::post('visits/{visitId}/prescription', [PrescriptionController::class, 'createForVisit']);
    Route::patch('visits/{visitId}/complete', [VisitController::class, 'complete']);
    Route::get('visits/{visitId}', [VisitController::class, 'show']);
    Route::patch('visits/{visitId}', [VisitController::class, 'update']);


    Route::post('visits/{visitId}/clinical-extraction', [ClinicalExtractionController::class, 'store']);
    Route::get('visits/{visitId}/clinical-extraction', [ClinicalExtractionController::class, 'show']);
    Route::post('visits/{visitId}/clinical-extraction/confirm', [ClinicalExtractionController::class, 'confirm']);
    Route::post('visits/{visitId}/clinical-extraction/reject', [ClinicalExtractionController::class, 'reject']);

    Route::get('prescriptions/{prescriptionId}', [PrescriptionController::class, 'show']);
    Route::patch('prescriptions/{prescriptionId}', [PrescriptionController::class, 'update']);
    Route::delete('prescriptions/{prescriptionId}', [PrescriptionController::class, 'destroy']);
    Route::get('prescriptions', [PrescriptionController::class, 'index']);
    Route::post('prescriptions/{prescriptionId}/finalize', [PrescriptionController::class, 'finalize']);

    Route::get('investigations/{investigationId}', [InvestigationController::class, 'show']);
    Route::patch('investigations/{investigationId}', [InvestigationController::class, 'update']);
    Route::delete('investigations/{investigationId}', [InvestigationController::class, 'destroy']);
    Route::get('investigations', [InvestigationController::class, 'index']);
    Route::post('investigations/{investigationId}/complete', [InvestigationController::class, 'complete']);

    Route::get('medicine-organizations', [DoctorMedicineOrganizationController::class, 'index']);
    Route::get('all-medicine-organizations', [DoctorMedicineOrganizationController::class, 'allOrganizations']);
    Route::post('medicine-organizations/{organizationId}/subscribe', [DoctorMedicineOrganizationController::class, 'subscribe']);
    Route::patch('medicine-organizations/{organizationId}/subscription', [DoctorMedicineOrganizationController::class, 'update']);
    Route::delete('medicine-organizations/{organizationId}/subscription', [DoctorMedicineOrganizationController::class, 'destroy']);
    Route::patch('medicine-organizations/{organizationId}/favorite', [DoctorMedicineOrganizationController::class, 'favorite']);

    Route::get('my-medicine-library', [DoctorMedicineLibraryController::class, 'index']);


    Route::post('logout', [DoctorAuthController::class, 'logout']);
});


// Doctor web session API
Route::middleware('auth:doctor_web')->prefix('doctor')->group(function () {
    Route::get('me', [DoctorAuthController::class, 'me']);
});
