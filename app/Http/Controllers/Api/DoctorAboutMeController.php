<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Doctor\StoreDoctorAboutMeRequest;
use App\Jobs\ProcessDoctorAboutMeMemory;
use App\Services\Doctor\DoctorAboutMeService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DoctorAboutMeController extends Controller
{
    public function index(
        Request $request,
        DoctorAboutMeService $doctorAboutMeService
    ): JsonResponse {
        $doctor = $request->user();

        $sample = $doctorAboutMeService->index(
            $doctor->id
        );

        return ApiResponse::success(
            message: 'Doctor About Me retrieved successfully.',
            data: [
                'about_me' => $sample,
            ]
        );
    }

    public function store(
        StoreDoctorAboutMeRequest $request,
        DoctorAboutMeService $doctorAboutMeService
    ): JsonResponse {
        $doctor = $request->user();

        $sample = $doctorAboutMeService->store(
            doctorId: $doctor->id,
            data: $request->validated(),
            inkFile: $request->file('ink_file')
        );

        ProcessDoctorAboutMeMemory::dispatch(
            $doctor->id,
            $sample->id
        )->afterCommit();

        return ApiResponse::created(
            message: 'Doctor About Me created successfully.',
            data: [
                'about_me' => $sample,
            ]
        );
    }

    public function update(
        StoreDoctorAboutMeRequest $request,
        DoctorAboutMeService $doctorAboutMeService
    ): JsonResponse {
        $doctor = $request->user();

        $data = $request->validated();

        if (isset($data['tool_data'])) {
            $data['tool_data'] = json_decode(
                $data['tool_data'],
                true
            );
        }

        $sample = $doctorAboutMeService->update(
            doctorId: $doctor->id,
            data: $data,
            inkFile: $request->file('ink_file')
        );

        if (!$sample) {
            return ApiResponse::notFound(
                'Doctor About Me not found.'
            );
        }

        ProcessDoctorAboutMeMemory::dispatch(
            $doctor->id,
            $sample->id
        )->afterCommit();

        return ApiResponse::success(
            message: 'Doctor About Me updated successfully.',
            data: [
                'about_me' => $sample,
            ]
        );
    }
}
