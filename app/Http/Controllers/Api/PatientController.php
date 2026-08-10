<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Patient\ClinicalHistoryRequest;
use App\Http\Requests\Patient\PatientStoreRequest;
use App\Models\Patient;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class PatientController extends Controller
{
    public function index(): JsonResponse
    {
        $patients = Patient::query()
            ->latest()
            ->get();

        return ApiResponse::success(
            message: 'Patients retrieved successfully.',
            data: [
                'patients' => $patients,
            ]
        );
    }

    public function store(PatientStoreRequest $request): JsonResponse
    {
        $patient = Patient::create(
            $request->validated()
        );

        return ApiResponse::created(
            message: 'Patient registered successfully.',
            data: [
                'patient' => $patient,
            ]
        );
    }

    public function show(int $patientId): JsonResponse
    {
        $patient = Patient::find($patientId);

        if (!$patient) {
            return ApiResponse::notFound(
                'Patient not found.'
            );
        }

        return ApiResponse::success(
            message: 'Patient retrieved successfully.',
            data: [
                'patient' => $patient,
            ]
        );
    }

    public function update(
        PatientStoreRequest $request,
        int $patientId
    ): JsonResponse {
        $patient = Patient::find($patientId);

        if (!$patient) {
            return ApiResponse::notFound(
                'Patient not found.'
            );
        }

        $patient->update(
            $request->validated()
        );

        return ApiResponse::success(
            message: 'Patient updated successfully.',
            data: [
                'patient' => $patient->fresh(),
            ]
        );
    }

    public function destroy(int $patientId): JsonResponse
    {
        $patient = Patient::find($patientId);

        if (!$patient) {
            return ApiResponse::notFound(
                'Patient not found.'
            );
        }

        $patient->delete();

        return ApiResponse::success(
            message: 'Patient deleted successfully.'
        );
    }

    public function clinicalHistory(
        ClinicalHistoryRequest $request,
        int $patientId
    ): JsonResponse {
        $patient = Patient::query()
            ->with([
                'visits' => function ($query) {
                    $query->latest('id');
                },
                'prescriptions' => function ($query) {
                    $query
                        ->with('items')
                        ->latest('id');
                },
                'investigations' => function ($query) {
                    $query
                        ->with('items')
                        ->latest('id');
                },
            ])
            ->find($patientId);

        if (!$patient) {
            return ApiResponse::notFound(
                'Patient not found.'
            );
        }

        return ApiResponse::success(
            message: 'Patient clinical history retrieved successfully.',
            data: [
                'patient' => $patient,
            ]
        );
    }
}
