<?php

use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\Auth\DoctorAuthController;
use App\Http\Controllers\Api\Auth\MasterAuthController;
use App\Http\Controllers\Api\Auth\MedicineOrganizationAuthController;
use App\Http\Controllers\Api\ClinicalExtractionController;
use App\Http\Controllers\Api\ClinicController;
use App\Http\Controllers\Api\ClinicPrescriptionTemplateController;
use App\Http\Controllers\Api\DoctorDashboardController;
use App\Http\Controllers\Api\DoctorHandwritingCorrectionController;
use App\Http\Controllers\Api\DoctorHandwritingSampleController;
use App\Http\Controllers\Api\DoctorMedicineLibraryController;
use App\Http\Controllers\Api\DoctorMedicineOrganizationController;
use App\Http\Controllers\Api\DevN8nDemoController;
use App\Http\Controllers\Api\GlobalInvestigationLibraryController;
use App\Http\Controllers\Api\GlobalMedicineLibraryController;
use App\Http\Controllers\Api\GlobalProcedureLibraryController;
use App\Http\Controllers\Api\InvestigationController;
use App\Http\Controllers\Api\InvestigationDocumentController;
use App\Http\Controllers\Api\MedicineOrganizationController;
use App\Http\Controllers\Api\MedicineOrganizationSubscriptionController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\OrganizationDashboardController;
use App\Http\Controllers\Api\OrganizationInvestigationController;
use App\Http\Controllers\Api\OrganizationReportController;
use App\Http\Controllers\Api\OrganizationMedicineController;
use App\Http\Controllers\Api\OrganizationProcedureController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\PrescriptionController;
use App\Http\Controllers\Api\PrescriptionShareController;
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

// Demo stand-in for the n8n order-conversion AI webhook, until that n8n
// workflow is built. Unauthenticated because a real n8n webhook call is
// server-to-server too. Remove once N8N_ORDER_CONVERSION_WEBHOOK points
// at the real workflow.
if (!app()->isProduction()) {
    Route::post('dev/n8n-demo/order-conversion', [DevN8nDemoController::class, 'orderConversion']);
}

// Authenticated Android doctor
Route::post('auth/web-login/approve', [WebLoginController::class, 'approve'])->middleware('auth:sanctum');

Route::post(
    'auth/web-login/complete',
    [WebLoginController::class, 'complete']
)->middleware('web.login.session');

//Medicine organization APIs
Route::middleware([
    'auth:sanctum',
    'medicine_organizations',
])->prefix('med')->group(function () {
    Route::get('me', [MedicineOrganizationAuthController::class, 'me']);
    Route::patch('me', [MedicineOrganizationAuthController::class, 'updateProfile']);
    Route::patch('password', [MedicineOrganizationAuthController::class, 'changePassword']);
    Route::post('logout', [MedicineOrganizationAuthController::class, 'logout']);

    Route::get('dashboard/summary', [OrganizationDashboardController::class, 'summary']);

    Route::get('reports/doctors', [OrganizationReportController::class, 'doctors']);
    Route::get('reports/unrecognised-medicines', [OrganizationReportController::class, 'unrecognisedMedicines']);

    Route::get('subscription-requests', [MedicineOrganizationSubscriptionController::class, 'index']);
    Route::patch('subscription-requests/{subscriptionId}', [MedicineOrganizationSubscriptionController::class, 'update']);

    Route::get('shared-prescriptions', [PrescriptionShareController::class, 'index']);
    Route::get('shared-prescriptions/{shareId}', [PrescriptionShareController::class, 'show']);
    Route::get('shared-prescriptions/{shareId}/handwriting/strokes', [PrescriptionShareController::class, 'handwritingStrokes']);
    Route::get('shared-prescriptions/{shareId}/prescription-template/header-image', [PrescriptionShareController::class, 'headerImage']);
    Route::get('shared-prescriptions/{shareId}/prescription-template/footer-image', [PrescriptionShareController::class, 'footerImage']);
    Route::patch('shared-prescriptions/{shareId}/status', [PrescriptionShareController::class, 'updateStatus']);
    Route::post('shared-prescriptions/{shareId}/convert-to-order', [OrderController::class, 'convertToOrder']);

    Route::get('orders', [OrderController::class, 'index']);
    Route::get('orders/{orderId}', [OrderController::class, 'show']);
    Route::patch('orders/{orderId}/submit', [OrderController::class, 'submit']);

    Route::get('medicines', [OrganizationMedicineController::class, 'index']);
    Route::post('medicines', [OrganizationMedicineController::class, 'store']);
    Route::get('medicines/{medicineId}', [OrganizationMedicineController::class, 'show']);
    Route::patch('medicines/{medicineId}', [OrganizationMedicineController::class, 'update']);
    Route::delete('medicines/{medicineId}', [OrganizationMedicineController::class, 'destroy']);

    Route::get('investigations', [OrganizationInvestigationController::class, 'index']);
    Route::post('investigations', [OrganizationInvestigationController::class, 'store']);
    Route::get('investigations/{investigationId}', [OrganizationInvestigationController::class, 'show']);
    Route::patch('investigations/{investigationId}', [OrganizationInvestigationController::class, 'update']);
    Route::delete('investigations/{investigationId}', [OrganizationInvestigationController::class, 'destroy']);

    Route::get('procedures', [OrganizationProcedureController::class, 'index']);
    Route::post('procedures', [OrganizationProcedureController::class, 'store']);
    Route::get('procedures/{procedureId}', [OrganizationProcedureController::class, 'show']);
    Route::patch('procedures/{procedureId}', [OrganizationProcedureController::class, 'update']);
    Route::delete('procedures/{procedureId}', [OrganizationProcedureController::class, 'destroy']);
});

// Master database APIs
Route::middleware([
    'auth:sanctum',
    'master_admins',
])->prefix('master')->group(function () {

    Route::get('me', [MasterAuthController::class, 'me']);
    Route::post('logout', [MasterAuthController::class, 'logout']);

    Route::get('medicine-organizations', [MedicineOrganizationController::class, 'index']);
    Route::post('medicine-organizations', [MedicineOrganizationController::class, 'store']);
    Route::get('medicine-organizations/{organizationId}', [MedicineOrganizationController::class, 'show']);
    Route::patch('medicine-organizations/{organizationId}', [MedicineOrganizationController::class, 'update']);
    Route::delete('medicine-organizations/{organizationId}', [MedicineOrganizationController::class, 'destroy']);

    Route::get('global-medicines', [GlobalMedicineLibraryController::class, 'index']);
    Route::post('global-medicines', [GlobalMedicineLibraryController::class, 'store']);
    Route::get('global-medicines/{medicineId}', [GlobalMedicineLibraryController::class, 'show']);
    Route::patch('global-medicines/{medicineId}', [GlobalMedicineLibraryController::class, 'update']);
    Route::delete('global-medicines/{medicineId}', [GlobalMedicineLibraryController::class, 'destroy']);

    Route::get('global-investigations', [GlobalInvestigationLibraryController::class, 'index']);
    Route::post('global-investigations', [GlobalInvestigationLibraryController::class, 'store']);
    Route::get('global-investigations/{investigationId}', [GlobalInvestigationLibraryController::class, 'show']);
    Route::patch('global-investigations/{investigationId}', [GlobalInvestigationLibraryController::class, 'update']);
    Route::delete('global-investigations/{investigationId}', [GlobalInvestigationLibraryController::class, 'destroy']);

    Route::get('global-procedures', [GlobalProcedureLibraryController::class, 'index']);
    Route::post('global-procedures', [GlobalProcedureLibraryController::class, 'store']);
    Route::get('global-procedures/{procedureId}', [GlobalProcedureLibraryController::class, 'show']);
    Route::patch('global-procedures/{procedureId}', [GlobalProcedureLibraryController::class, 'update']);
    Route::delete('global-procedures/{procedureId}', [GlobalProcedureLibraryController::class, 'destroy']);
});






//// Doctor-tenant APIs
Route::middleware([
    'doctor.auth',
    // 'auth:sanctum',
    'doctor.tenant'
])->prefix('doctor')->group(function () {


    Route::get('me', [DoctorAuthController::class, 'me']);
    Route::patch('me', [DoctorAuthController::class, 'updateProfile']);
    Route::patch('password', [DoctorAuthController::class, 'changePassword']);

    Route::get('handwriting-samples', [DoctorHandwritingSampleController::class, 'index']);
    Route::get('handwriting-samples/file', [DoctorHandwritingSampleController::class, 'file']);
    Route::get('handwriting-samples/strokes',[DoctorHandwritingSampleController::class, 'strokes']);
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
    Route::post('queues/{queueId}/call', [QueueController::class, 'callSpecific']);
    Route::post('queues/call-next', [QueueController::class, 'callNext']);
    Route::post('queues/call-reset-next', [QueueController::class, 'resetCallNext']);
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

    Route::get('clinics/{clinicId}/prescription-template', [ClinicPrescriptionTemplateController::class, 'show']);
    Route::post('clinics/{clinicId}/prescription-template', [ClinicPrescriptionTemplateController::class, 'store']);
    Route::get('clinics/{clinicId}/prescription-template/header-image', [ClinicPrescriptionTemplateController::class, 'headerImage']);
    Route::get('clinics/{clinicId}/prescription-template/footer-image', [ClinicPrescriptionTemplateController::class, 'footerImage']);

    Route::get('dashboard/summary', [DoctorDashboardController::class, 'summary']);

    Route::get('visits', [VisitController::class, 'index']); //Date-wise consultation list
    Route::post('visits/start', [VisitController::class, 'start']);
    Route::post('visits/direct', [VisitController::class, 'direct']); //Emergency
    Route::delete('visits/{visitId}/reset-direct', [VisitController::class, 'resetDirect']); //Undo accidental Emergency click
    Route::post('visits/{visitId}/prescription', [PrescriptionController::class, 'createForVisit']);
    Route::patch('visits/{visitId}/complete', [VisitController::class, 'complete']);
    Route::patch('visits/{visitId}/emergency-complete', [VisitController::class, 'emergencyComplete']); //Finished button: skip review, save as unverified
    Route::patch('visits/{visitId}/reopen', [VisitController::class, 'reopen']); //Resume review of an unverified prescription: re-runs steps 4-12
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
    Route::get('prescriptions/{prescriptionId}/shares', [PrescriptionShareController::class, 'doctorIndex']);
    Route::get('shared-prescriptions', [PrescriptionShareController::class, 'doctorList']);

    Route::get('investigations/{investigationId}', [InvestigationController::class, 'show']);
    Route::patch('investigations/{investigationId}', [InvestigationController::class, 'update']);
    Route::delete('investigations/{investigationId}', [InvestigationController::class, 'destroy']);
    Route::get('investigations', [InvestigationController::class, 'index']);
    Route::post('investigations/{investigationId}/complete', [InvestigationController::class, 'complete']);
    Route::post('investigations/{investigationId}/documents', [InvestigationDocumentController::class, 'store']);
    Route::get('investigations/{investigationId}/documents/{documentId}/file', [InvestigationDocumentController::class, 'file']);
    Route::delete('investigations/{investigationId}/documents/{documentId}', [InvestigationDocumentController::class, 'destroy']);

    Route::get('medicine-organizations', [DoctorMedicineOrganizationController::class, 'index']);
    Route::get('all-medicine-organizations', [DoctorMedicineOrganizationController::class, 'allOrganizations']);
    Route::post('medicine-organizations/{organizationId}/subscribe', [DoctorMedicineOrganizationController::class, 'subscribe']);
    Route::patch('medicine-organizations/{organizationId}/subscription', [DoctorMedicineOrganizationController::class, 'update']);
    Route::delete('medicine-organizations/{organizationId}/subscription', [DoctorMedicineOrganizationController::class, 'destroy']);
    Route::patch('medicine-organizations/{organizationId}/favorite', [DoctorMedicineOrganizationController::class, 'favorite']);

    Route::get('my-medicine-library', [DoctorMedicineLibraryController::class, 'index']);


    Route::post('logout', [DoctorAuthController::class, 'logout']);
});
