<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ErrorLog\ErrorLogStoreRequest;
use App\Http\Requests\ErrorLog\ErrorLogUpdateStatusRequest;
use App\Models\ErrorLog;
use App\Support\ApiResponse;
use App\Support\ErrorLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ErrorLogController extends Controller
{
    /**
     * Doctor (Android / iOS / Web) and medicine organization (Web)
     * report a client-side error.
     */
    public function store(ErrorLogStoreRequest $request): JsonResponse
    {
        $errorLog = ErrorLogger::fromClient(
            $request,
            $request->validated()
        );

        if (!$errorLog) {
            return ApiResponse::error(
                'Error log could not be saved.',
                null,
                500
            );
        }

        return ApiResponse::created(
            message: 'Error logged successfully.',
            data: [
                'error_log_id' => $errorLog->id,
            ]
        );
    }

    /**
     * Master admin: list errors with filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ErrorLog::query();

        foreach (['user_type', 'platform', 'severity', 'status', 'screen_name'] as $field) {
            if ($request->filled($field)) {
                $query->where(
                    $field,
                    $request->string($field)->toString()
                );
            }
        }

        if ($request->filled('login_user_id')) {
            $query->where(
                'login_user_id',
                $request->integer('login_user_id')
            );
        }

        if ($request->filled('from_date')) {
            $query->whereDate(
                'created_at',
                '>=',
                $request->date('from_date')
            );
        }

        if ($request->filled('to_date')) {
            $query->whereDate(
                'created_at',
                '<=',
                $request->date('to_date')
            );
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();

            $query->where(function ($q) use ($search) {
                $q->where('error_description', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('function_name', 'like', "%{$search}%")
                    ->orWhere('api_endpoint', 'like', "%{$search}%");
            });
        }

        $errorLogs = $query
            ->select(
                'id',
                'user_type',
                'login_user_id',
                'name',
                'platform',
                'screen_name',
                'function_name',
                'error_description',
                'severity',
                'http_status',
                'api_endpoint',
                'status',
                'created_at'
            )
            ->latest('id')
            ->paginate(
                $request->integer('per_page', 20)
            );

        return ApiResponse::success(
            message: 'Error logs retrieved successfully.',
            data: [
                'error_logs' => $errorLogs,
            ]
        );
    }

    /**
     * Master admin: counts for the error screen header.
     */
    public function summary(): JsonResponse
    {
        return ApiResponse::success(
            message: 'Error log summary retrieved successfully.',
            data: [
                'by_status' => ErrorLog::query()
                    ->selectRaw('status, COUNT(*) as total')
                    ->groupBy('status')
                    ->pluck('total', 'status'),
                'open_by_platform' => ErrorLog::query()
                    ->where('status', 'open')
                    ->selectRaw('platform, COUNT(*) as total')
                    ->groupBy('platform')
                    ->pluck('total', 'platform'),
                'open_by_user_type' => ErrorLog::query()
                    ->where('status', 'open')
                    ->selectRaw('user_type, COUNT(*) as total')
                    ->groupBy('user_type')
                    ->pluck('total', 'user_type'),
            ]
        );
    }

    /**
     * Master admin: full error detail including stack trace and payload.
     */
    public function show(int $errorLogId): JsonResponse
    {
        $errorLog = ErrorLog::with('resolver:id,name,email')
            ->find($errorLogId);

        if (!$errorLog) {
            return ApiResponse::notFound(
                'Error log not found.'
            );
        }

        return ApiResponse::success(
            message: 'Error log retrieved successfully.',
            data: [
                'error_log' => $errorLog,
            ]
        );
    }

    /**
     * Master admin: move an error through open → in_progress → resolved / ignored.
     */
    public function updateStatus(
        ErrorLogUpdateStatusRequest $request,
        int $errorLogId
    ): JsonResponse {
        $errorLog = ErrorLog::find($errorLogId);

        if (!$errorLog) {
            return ApiResponse::notFound(
                'Error log not found.'
            );
        }

        $status = $request->validated('status');
        $isClosed = in_array($status, ['resolved', 'ignored'], true);

        $errorLog->update([
            'status' => $status,
            'resolution_note' => $request->validated('resolution_note', $errorLog->resolution_note),
            'resolved_by' => $isClosed ? $request->user()->id : null,
            'resolved_at' => $isClosed ? now() : null,
        ]);

        return ApiResponse::success(
            message: 'Error log status updated successfully.',
            data: [
                'error_log' => $errorLog->fresh('resolver:id,name,email'),
            ]
        );
    }

    public function destroy(int $errorLogId): JsonResponse
    {
        $errorLog = ErrorLog::find($errorLogId);

        if (!$errorLog) {
            return ApiResponse::notFound(
                'Error log not found.'
            );
        }

        $errorLog->delete();

        return ApiResponse::success(
            message: 'Error log deleted successfully.'
        );
    }
}
