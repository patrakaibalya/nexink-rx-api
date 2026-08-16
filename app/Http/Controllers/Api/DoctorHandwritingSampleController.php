<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Doctor\DoctorHandwritingSampleRequest;
use App\Jobs\ProcessDoctorHandwritingMemory;
use App\Services\Doctor\DoctorHandwritingSampleService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DoctorHandwritingSampleController extends Controller
{
    public function index(
        Request $request,
        DoctorHandwritingSampleService $doctorHandwritingSampleService
    ): JsonResponse {
        $doctor = $request->user();

        $data = Validator::make(
            $request->query(),
            [
                'sample_type' => [
                    'required',
                    'string',
                    'in:about_me,prescription',
                ],

                'prescription_id' => [
                    'nullable',
                    'integer',
                    'min:1',
                    'required_if:sample_type,prescription',
                    'prohibited_if:sample_type,about_me',
                ],
            ]
        )->validate();

        $sample = $doctorHandwritingSampleService->index(
            doctorId: $doctor->id,
            sampleType: $data['sample_type'],
            prescriptionId: isset($data['prescription_id'])
                ? (int) $data['prescription_id']
                : null
        );

        return ApiResponse::success(
            message: 'Doctor handwriting sample retrieved successfully.',
            data: [
                'handwriting_sample' => $sample,
            ]
        );
    }

    public function store(
        DoctorHandwritingSampleRequest $request,
        DoctorHandwritingSampleService $doctorHandwritingSampleService
    ): JsonResponse {
        $doctor = $request->user();

        $data = $request->validated();

        if (isset($data['tool_data'])) {
            $data['tool_data'] = json_decode(
                $data['tool_data'],
                true
            );
        }

        $sample = $doctorHandwritingSampleService->store(
            doctorId: $doctor->id,
            sampleType: $data['sample_type'],
            prescriptionId: isset($data['prescription_id'])
                ? (int) $data['prescription_id']
                : null,
            data: $data,
            inkFile: $request->file('ink_file')
        );

        ProcessDoctorHandwritingMemory::dispatch(
            $doctor->id,
            $sample->id
        )->afterCommit();

        return ApiResponse::created(
            message: 'Doctor handwriting sample created successfully.',
            data: [
                'handwriting_sample' => $sample,
            ]
        );
    }

    public function update(
        DoctorHandwritingSampleRequest $request,
        DoctorHandwritingSampleService $doctorHandwritingSampleService
    ): JsonResponse {
        $doctor = $request->user();

        $data = $request->validated();

        if (isset($data['tool_data'])) {
            $data['tool_data'] = json_decode(
                $data['tool_data'],
                true
            );
        }

        $sample = $doctorHandwritingSampleService->update(
            doctorId: $doctor->id,
            sampleType: $data['sample_type'],
            prescriptionId: isset($data['prescription_id'])
                ? (int) $data['prescription_id']
                : null,
            data: $data,
            inkFile: $request->file('ink_file')
        );

        if (!$sample) {
            return ApiResponse::notFound(
                'Doctor handwriting sample not found.'
            );
        }

        ProcessDoctorHandwritingMemory::dispatch(
            $doctor->id,
            $sample->id
        )->afterCommit();

        return ApiResponse::success(
            message: 'Doctor handwriting sample updated successfully.',
            data: [
                'handwriting_sample' => $sample,
            ]
        );
    }
}
