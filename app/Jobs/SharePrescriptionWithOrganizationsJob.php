<?php

namespace App\Jobs;

use App\Models\ClinicalExtraction;
use App\Models\DoctorHandwritingSample;
use App\Models\DoctorMedicineSubscription;
use App\Models\Prescription;
use App\Models\PrescriptionShare;
use App\Support\DoctorTenantConnector;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SharePrescriptionWithOrganizationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        public int $extractionId,
        public int $doctorId
    ) {}

    public function handle(): void
    {
        $subscriptions = DoctorMedicineSubscription::query()
            ->where('doctor_id', $this->doctorId)
            ->where('status', 'approved')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->get();

        if ($subscriptions->isEmpty()) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Configure Doctor Tenant Connection
        |--------------------------------------------------------------------------
        */

        if (!DoctorTenantConnector::connect($this->doctorId)) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Load Confirmed Extraction
        |--------------------------------------------------------------------------
        */

        $extraction = ClinicalExtraction::query()
            ->with(['clinic', 'patient'])
            ->find($this->extractionId);

        if (!$extraction || $extraction->status !== 'confirmed') {
            return;
        }

        $prescription = Prescription::query()
            ->where('visit_id', $extraction->visit_id)
            ->latest('id')
            ->first();

        if (!$prescription) {
            return;
        }

        $patient = $extraction->patient;
        $clinic = $extraction->clinic;

        $patientSnapshot = [
            'name' => $patient->name,
            'mobile' => $patient->mobile,
            'gender' => $patient->gender,
            'date_of_birth' => $patient->date_of_birth?->toDateString(),
            'blood_group' => $patient->blood_group,
        ];

        $clinicSnapshot = $clinic ? [
            'name' => $clinic->name,
            'address' => $clinic->address,
            'city' => $clinic->city,
            'mobile' => $clinic->mobile,
        ] : null;

        $payload = [
            'diagnoses' => $extraction->payload['diagnoses'] ?? [],
            'medicines' => $extraction->payload['medicines'] ?? [],
            'instructions' => $extraction->payload['instructions'] ?? [],
            'follow_up' => $extraction->payload['follow_up'] ?? null,
        ];

        $hasHandwritingSample = DoctorHandwritingSample::query()
            ->where('sample_type', 'prescription')
            ->where('prescription_id', $prescription->id)
            ->whereNotNull('ink_file_path')
            ->exists();

        /*
        |--------------------------------------------------------------------------
        | Fan Out To Subscribed Organizations
        |--------------------------------------------------------------------------
        */

        foreach ($subscriptions as $subscription) {
            PrescriptionShare::query()->updateOrCreate(
                [
                    'organization_id' => $subscription->organization_id,
                    'clinical_extraction_id' => $extraction->id,
                ],
                [
                    'doctor_id' => $this->doctorId,
                    'visit_id' => $extraction->visit_id,
                    'patient_id' => $extraction->patient_id,
                    'prescription_id' => $prescription->id,
                    'patient_snapshot' => $patientSnapshot,
                    'clinic_snapshot' => $clinicSnapshot,
                    'payload' => $payload,
                    'has_handwriting_sample' => $hasHandwritingSample,
                    'status' => 'sent',
                    'shared_at' => now(),
                ]
            );
        }
    }
}
