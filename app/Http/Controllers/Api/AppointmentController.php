<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Appointment\AppointmentAvailabilityRequest;
use App\Http\Requests\Appointment\AppointmentIndexRequest;
use App\Http\Requests\Appointment\AppointmentStatusRequest;
use App\Http\Requests\Appointment\AppointmentStoreRequest;
use App\Http\Requests\Appointment\AppointmentUpdateRequest;
use App\Http\Requests\Appointment\PatientAppointmentStoreRequest;
use App\Services\Appointment\AppointmentService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class AppointmentController extends Controller
{
    public function store(
        AppointmentStoreRequest $request,
        AppointmentService $appointmentService
    ): JsonResponse {
        $appointment = $appointmentService->create(
            $request->validated()
        );

        return ApiResponse::created(
            message: 'Appointment created successfully.',
            data: [
                'appointment' => $appointment,
            ]
        );
    }

    public function index(
        AppointmentIndexRequest $request,
        AppointmentService $appointmentService
    ): JsonResponse {
        $appointments = $appointmentService->list(
            $request->validated()
        );

        return ApiResponse::success(
            message: 'Appointments retrieved successfully.',
            data: [
                'appointments' => $appointments,
            ]
        );
    }

    public function show(
        AppointmentService $appointmentService,
        int $appointmentId
    ): JsonResponse {
        $appointment = $appointmentService->find(
            $appointmentId
        );

        return ApiResponse::success(
            message: 'Appointment retrieved successfully.',
            data: [
                'appointment' => $appointment,
            ]
        );
    }

    public function update(
        AppointmentUpdateRequest $request,
        AppointmentService $appointmentService,
        int $appointmentId
    ): JsonResponse {
        $appointment = $appointmentService->update(
            $appointmentId,
            $request->validated()
        );

        return ApiResponse::success(
            message: 'Appointment updated successfully.',
            data: [
                'appointment' => $appointment,
            ]
        );
    }

    public function destroy(
        AppointmentService $appointmentService,
        int $appointmentId
    ): JsonResponse {
        $appointmentService->delete(
            $appointmentId
        );

        return ApiResponse::success(
            message: 'Appointment deleted successfully.'
        );
    }


    public function updateStatus(
        AppointmentStatusRequest $request,
        AppointmentService $appointmentService,
        int $appointmentId
    ): JsonResponse {
        $appointment = $appointmentService->updateStatus(
            $appointmentId,
            $request->input('status')
        );

        return ApiResponse::success(
            message: 'Appointment status updated successfully.',
            data: [
                'appointment' => $appointment,
            ]
        );
    }

    public function arrive(
        int $appointmentId
    ): JsonResponse {
        $result = app(
            \App\Services\Appointment\AppointmentService::class
        )->arrive($appointmentId);

        return ApiResponse::success(
            message: 'Patient arrived and added to queue successfully.',
            data: $result
        );
    }

    public function bookForPatient(
        PatientAppointmentStoreRequest $request,
        AppointmentService $appointmentService
    ): JsonResponse {
        $appointment = $appointmentService->bookForPatient(
            $request->validated()
        );

        return ApiResponse::created(
            message: 'Appointment booked successfully.',
            data: [
                'appointment' => $appointment,
            ]
        );
    }

    public function availability(
        AppointmentAvailabilityRequest $request,
        AppointmentService $appointmentService
    ): JsonResponse {
        $availability = $appointmentService->availability(
            $request->integer('clinic_id'),
            $request->input('date')
        );

        return ApiResponse::success(
            message: 'Appointment availability retrieved successfully.',
            data: [
                'availability' => $availability,
            ]
        );
    }
}
