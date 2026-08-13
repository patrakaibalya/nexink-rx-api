<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Queue\CallNextQueueRequest;
use App\Http\Requests\Queue\CurrentQueueRequest;
use App\Http\Requests\Queue\QueueHistoryRequest;
use App\Http\Requests\Queue\QueueIndexRequest;
use App\Http\Requests\Queue\QueueStatusRequest;
use App\Http\Requests\Queue\QueueStoreRequest;
use App\Http\Requests\Queue\QueueSummaryRequest;
use App\Models\Queue;
use App\Services\Queue\QueueService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class QueueController extends Controller
{
    public function store(
        QueueStoreRequest $request,
        QueueService $queueService
    ): JsonResponse {
        $queue = $queueService->create(
            $request->validated()
        );

        $queue->load([
            'clinic',
            'patient',
        ]);

        return ApiResponse::created(
            message: 'Queue created successfully.',
            data: [
                'queue' => $queue,
            ]
        );
    }

    public function index(
        QueueIndexRequest $request
    ): JsonResponse {
        $date = $request->input(
            'date',
            now()->toDateString()
        );

        $query = Queue::query()
            ->with([
                'clinic',
                'patient',
            ])
            ->whereDate('queue_date', $date)
            ->orderBy('queue_number');

        if ($request->filled('clinic_id')) {
            $query->where(
                'clinic_id',
                $request->integer('clinic_id')
            );
        }

        if ($request->filled('status')) {
            $query->where(
                'status',
                $request->input('status')
            );
        }

        $queues = $query->get();

        return ApiResponse::success(
            message: 'Queues retrieved successfully.',
            data: [
                'date' => $date,
                'queues' => $queues,
            ]
        );
    }

    public function updateStatus(
        QueueStatusRequest $request,
        QueueService $queueService,
        int $queueId
    ): JsonResponse {
        $queue = $queueService->updateStatus(
            $queueId,
            $request->input('status')
        );

        return ApiResponse::success(
            message: 'Queue status updated successfully.',
            data: [
                'queue' => $queue,
            ]
        );
    }

    public function callNext(
        CallNextQueueRequest $request,
        QueueService $queueService
    ): JsonResponse {
        $queue = $queueService->callNext(
            $request->integer('clinic_id')
        );

        if (!$queue) {
            return ApiResponse::success(
                message: 'No waiting patients in the queue.',
                data: [
                    'queue' => null,
                ]
            );
        }

        return ApiResponse::success(
            message: 'Next patient called successfully.',
            data: [
                'queue' => $queue,
            ]
        );
    }

    public function current(
        CurrentQueueRequest $request,
        QueueService $queueService
    ): JsonResponse {
        $queue = $queueService->current(
            $request->integer('clinic_id')
        );

        if (!$queue) {
            return ApiResponse::success(
                message: 'No active patient in the queue.',
                data: [
                    'queue' => null,
                ]
            );
        }

        return ApiResponse::success(
            message: 'Current queue retrieved successfully.',
            data: [
                'queue' => $queue,
            ]
        );
    }

    public function summary(
        QueueSummaryRequest $request,
        QueueService $queueService
    ): JsonResponse {
        $summary = $queueService->summary(
            $request->integer('clinic_id'),
            $request->input('date')
        );

        return ApiResponse::success(
            message: 'Queue summary retrieved successfully.',
            data: [
                'summary' => $summary,
            ]
        );
    }

    public function history(
        QueueHistoryRequest $request,
        QueueService $queueService
    ): JsonResponse {
        $queues = $queueService->history(
            $request->validated()
        );

        return ApiResponse::success(
            message: 'Queue history retrieved successfully.',
            data: [
                'from' => $request->input('from'),
                'to' => $request->input('to'),
                'queues' => $queues,
            ]
        );
    }
}
