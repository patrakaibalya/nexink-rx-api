<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    public static function success(
        string $message = 'Success.',
        mixed $data = null,
        int $status = 200
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    public static function created(
        string $message = 'Created successfully.',
        mixed $data = null
    ): JsonResponse {
        return self::success(
            message: $message,
            data: $data,
            status: 201
        );
    }

    public static function error(
        string $message = 'Something went wrong.',
        mixed $data = null,
        int $status = 400
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    public static function unauthorized(
        string $message = 'Unauthorized.'
    ): JsonResponse {
        return self::error(
            message: $message,
            status: 401
        );
    }

    public static function forbidden(
        string $message = 'Forbidden.'
    ): JsonResponse {
        return self::error(
            message: $message,
            status: 403
        );
    }

    public static function notFound(
        string $message = 'Resource not found.'
    ): JsonResponse {
        return self::error(
            message: $message,
            status: 404
        );
    }

    public static function validationError(
        string $message = 'Validation failed.',
        mixed $data = null
    ): JsonResponse {
        return self::error(
            message: $message,
            data: $data,
            status: 422
        );
    }
}
