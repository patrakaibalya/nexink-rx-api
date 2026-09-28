<?php

namespace App\Support;

use App\Models\DoctorAccount;
use App\Models\ErrorLog;
use App\Models\MasterAdmin;
use App\Models\MedicineOrganization;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ErrorLogger
{
    /**
     * Keys stripped from request payloads before they are stored.
     */
    private const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
        'token',
        'access_token',
        'refresh_token',
        'otp',
    ];

    /**
     * Store an error reported by a client app (Android / iOS / Web).
     */
    public static function fromClient(
        Request $request,
        array $data
    ): ?ErrorLog {
        return self::store(array_merge(
            self::userContext($request->user()),
            self::requestContext($request),
            [
                'api_endpoint' => $data['api_endpoint'] ?? null,
                'http_method' => $data['http_method'] ?? null,
                'request_payload' => isset($data['request_payload'])
                    ? self::sanitize($data['request_payload'])
                    : null,
            ],
            Arr::only($data, [
                'platform',
                'screen_name',
                'function_name',
                'error_description',
                'error_code',
                'stack_trace',
                'severity',
                'http_status',
                'app_version',
                'device_info',
            ])
        ));
    }

    /**
     * Store an unhandled server exception raised while serving a request.
     */
    public static function fromException(
        Throwable $exception,
        ?Request $request = null
    ): ?ErrorLog {
        $request ??= request();

        $route = $request->route();

        return self::store(array_merge(
            self::userContext($request->user()),
            self::requestContext($request),
            [
                'platform' => self::headerPlatform($request) ?? 'server',
                'screen_name' => $request->header('X-Screen-Name'),
                'function_name' => $route?->getActionName(),
                'error_description' => $exception->getMessage() ?: class_basename($exception),
                'error_code' => (string) $exception->getCode() ?: null,
                'exception_class' => get_class($exception),
                'stack_trace' => $exception->getFile() . ':' . $exception->getLine()
                    . "\n" . $exception->getTraceAsString(),
                'severity' => 'high',
                'api_endpoint' => '/' . ltrim($request->path(), '/'),
                'http_method' => $request->method(),
                'http_status' => method_exists($exception, 'getStatusCode')
                    ? $exception->getStatusCode()
                    : 500,
                'request_payload' => self::sanitize(
                    $request->except(array_keys($request->allFiles()))
                ),
                'app_version' => $request->header('X-App-Version'),
                'device_info' => $request->header('X-Device-Info'),
            ]
        ));
    }

    /**
     * Logging must never break the request, so failures only go to the log file.
     */
    private static function store(array $attributes): ?ErrorLog
    {
        try {
            $attributes['error_description'] = Str::limit(
                (string) ($attributes['error_description'] ?? 'Unknown error'),
                10000
            );

            foreach (['name', 'screen_name', 'function_name', 'device_info'] as $key) {
                if (isset($attributes[$key])) {
                    $attributes[$key] = Str::limit((string) $attributes[$key], 250);
                }
            }

            if (isset($attributes['api_endpoint'])) {
                $attributes['api_endpoint'] = Str::limit((string) $attributes['api_endpoint'], 490);
            }

            return ErrorLog::create(array_filter(
                $attributes,
                fn($value) => $value !== null
            ));
        } catch (Throwable $e) {
            Log::error('Failed to write error_logs row.', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private static function userContext(mixed $user): array
    {
        return match (true) {
            $user instanceof DoctorAccount => [
                'user_type' => ErrorLog::USER_TYPE_DOCTOR,
                'login_user_id' => $user->id,
                'name' => $user->name,
            ],
            $user instanceof MedicineOrganization => [
                'user_type' => ErrorLog::USER_TYPE_MEDICINE_ORGANIZATION,
                'login_user_id' => $user->id,
                'name' => $user->organization_name,
            ],
            $user instanceof MasterAdmin => [
                'user_type' => ErrorLog::USER_TYPE_MASTER_ADMIN,
                'login_user_id' => $user->id,
                'name' => $user->name,
            ],
            default => [
                'user_type' => ErrorLog::USER_TYPE_GUEST,
            ],
        };
    }

    private static function requestContext(Request $request): array
    {
        return [
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 490) ?: null,
        ];
    }

    private static function headerPlatform(Request $request): ?string
    {
        $platform = strtolower((string) $request->header('X-Platform'));

        return in_array($platform, ErrorLog::PLATFORMS, true)
            ? $platform
            : null;
    }

    private static function sanitize(mixed $payload): mixed
    {
        if (!is_array($payload)) {
            return $payload;
        }

        foreach ($payload as $key => $value) {
            if (in_array(strtolower((string) $key), self::SENSITIVE_KEYS, true)) {
                $payload[$key] = '***';
            } elseif (is_array($value)) {
                $payload[$key] = self::sanitize($value);
            }
        }

        return $payload;
    }
}
