<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Doctor\DoctorHandwritingSampleRequest;
use App\Jobs\ProcessDoctorHandwritingMemory;
use App\Services\Doctor\DoctorHandwritingSampleService;
use App\Services\Doctor\DoctorHandwritingStrokeService;
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
                    'in:prescription',
                ],

                'prescription_id' => [
                    'required',
                    'integer',
                    'min:1',
                ],

                'page_number' => [
                    'nullable',
                    'integer',
                    'min:1',
                ],
            ]
        )->validate();

        $sample = $doctorHandwritingSampleService->index(
            doctorId: $doctor->id,
            sampleType: $data['sample_type'],
            prescriptionId: isset($data['prescription_id'])
                ? (int) $data['prescription_id']
                : null,
            pageNumber: isset($data['page_number'])
                ? (int) $data['page_number']
                : null
        );

        return ApiResponse::success(
            message: 'Doctor handwriting sample retrieved successfully.',
            data: [
                'handwriting_sample' => $sample,
            ]
        );
    }

    public function pages(
        Request $request,
        DoctorHandwritingSampleService $doctorHandwritingSampleService
    ): JsonResponse {
        $doctor = $request->user();

        $data = Validator::make(
            $request->query(),
            [
                'prescription_id' => [
                    'required',
                    'integer',
                    'min:1',
                ],
            ]
        )->validate();

        $pages = $doctorHandwritingSampleService->pages(
            doctorId: $doctor->id,
            prescriptionId: (int) $data['prescription_id']
        );

        return ApiResponse::success(
            message: 'Doctor handwriting sample pages retrieved successfully.',
            data: [
                'pages' => $pages,
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
            inkFile: $request->file('ink_file'),
            pageNumber: isset($data['page_number'])
                ? (int) $data['page_number']
                : null
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
            inkFile: $request->file('ink_file'),
            pageNumber: isset($data['page_number'])
                ? (int) $data['page_number']
                : null
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

    public function file(
        Request $request,
        DoctorHandwritingSampleService $doctorHandwritingSampleService
    ) {
        $doctor = $request->user();

        $data = Validator::make(
            $request->query(),
            [
                'sample_type' => [
                    'required',
                    'string',
                    'in:prescription',
                ],

                'prescription_id' => [
                    'required',
                    'integer',
                    'min:1',
                ],

                'page_number' => [
                    'nullable',
                    'integer',
                    'min:1',
                ],
            ]
        )->validate();

        $sample = $doctorHandwritingSampleService->index(
            doctorId: $doctor->id,
            sampleType: $data['sample_type'],
            prescriptionId: isset($data['prescription_id'])
                ? (int) $data['prescription_id']
                : null,
            pageNumber: isset($data['page_number'])
                ? (int) $data['page_number']
                : null
        );

        if (!$sample || !$sample->ink_file_path) {
            return ApiResponse::notFound(
                'Doctor handwriting file not found.'
            );
        }

        return $doctorHandwritingSampleService->downloadFile(
            $sample
        );
    }


    public function strokes(
        Request $request,
        DoctorHandwritingSampleService $service,
        DoctorHandwritingStrokeService $strokeService
    ): JsonResponse {
        $doctor = $request->user();

        $data = Validator::make($request->query(), [
            'sample_type' => [
                'required',
                'string',
                'in:prescription',
            ],
            'prescription_id' => [
                'required',
                'integer',
                'min:1',
            ],
            'page_number' => [
                'nullable',
                'integer',
                'min:1',
            ],
        ])->validate();

        $sample = $service->index(
            doctorId: $doctor->id,
            sampleType: $data['sample_type'],
            prescriptionId: isset($data['prescription_id'])
                ? (int) $data['prescription_id']
                : null,
            pageNumber: isset($data['page_number'])
                ? (int) $data['page_number']
                : null
        );

        if (!$sample || !$sample->ink_file_path) {
            return ApiResponse::notFound(
                'Doctor handwriting sample not found.'
            );
        }

        try {
            $data = $strokeService->decode($sample);
        } catch (\RuntimeException $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                data: null,
                status: 500
            );
        }

        return ApiResponse::success(
            message: 'Doctor handwriting strokes retrieved successfully.',
            data: $data
        );
    }
}
