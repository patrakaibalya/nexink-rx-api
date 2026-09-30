<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Patient\ClinicalHistoryRequest;
use App\Http\Requests\Patient\PatientIndexRequest;
use App\Http\Requests\Patient\PatientStoreRequest;
use App\Models\Patient;
use App\Services\Patient\PatientVitalService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class PatientController extends Controller
{
    /**
     * Paginated roster, newest first. `search` matches name, mobile or
     * email; `gender=other` covers anything that isn't male / female.
     */
    public function index(PatientIndexRequest $request): JsonResponse
    {
        $search = trim((string) $request->input('search', ''));
        $gender = $request->input('gender');

        $patients = Patient::query()
            ->with('latestVital')
            ->when($search !== '', function ($query) use ($search) {
                $like = '%' . addcslashes($search, '%_\\') . '%';

                $query->where(function ($query) use ($like) {
                    $query->where('name', 'like', $like)
                        ->orWhere('mobile', 'like', $like)
                        ->orWhere('email', 'like', $like);
                });
            })
            ->when(in_array($gender, ['male', 'female'], true), fn ($query) => $query->where('gender', $gender))
            ->when($gender === 'other', function ($query) {
                $query->where(function ($query) {
                    $query->whereNull('gender')
                        ->orWhereNotIn('gender', ['male', 'female']);
                });
            })
            ->latest()
            ->latest('id')
            ->paginate(
                perPage: $request->integer('per_page', 15),
                page: $request->integer('page', 1)
            );

        return ApiResponse::success(
            message: 'Patients retrieved successfully.',
            data: [
                'patients' => $patients,
            ]
        );
    }

    public function store(
        PatientStoreRequest $request,
        PatientVitalService $vitalService
    ): JsonResponse {
        $patient = Patient::create(
            $request->validated()
        );

        $vitalService->recordFromProfile($patient, $request->validated());

        return ApiResponse::created(
            message: 'Patient registered successfully.',
            data: [
                'patient' => $patient,
            ]
        );
    }

    public function show(int $patientId): JsonResponse
    {
        $patient = Patient::with('latestVital')->find($patientId);

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
        PatientVitalService $vitalService,
        int $patientId
    ): JsonResponse {
        $patient = Patient::find($patientId);

        if (!$patient) {
            return ApiResponse::notFound(
                'Patient not found.'
            );
        }

        $previous = $patient->only(['weight', 'height', 'blood_pressure_result', 'diabetic_result', 'thyroid_result']);

        $patient->update(
            $request->validated()
        );

        $vitalService->recordFromProfile($patient, $request->validated(), $previous);

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
                        ->with(['items','handwritingSamples'])
                        ->latest('id');
                },
                'investigations' => function ($query) {
                    $query
                        ->with('items')
                        ->latest('id');
                },
                'procedures' => function ($query) {
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
