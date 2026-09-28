<?php

use App\Http\Middleware\DoctorTenantMiddleware;
use App\Http\Middleware\DoctorWebOrSanctumMiddleware;
use App\Http\Middleware\MasterAdminMiddleware;
use App\Http\Middleware\MedicineOrganizationMiddleware;
use App\Support\ApiResponse;
use App\Support\ErrorLogger;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function ($middleware) {
        $middleware->alias([
            'doctor.tenant' => DoctorTenantMiddleware::class,
            'master_admins' => MasterAdminMiddleware::class,
            'medicine_organizations' => MedicineOrganizationMiddleware::class,
            'web.login.session' => \App\Http\Middleware\StartWebLoginSession::class,
            'doctor.auth' => DoctorWebOrSanctumMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Store unhandled server errors from the doctor and medicine
        // organization APIs so master admins can review them. Validation,
        // auth and HTTP (404/403...) exceptions are not reported by Laravel,
        // so they never reach this callback.
        $exceptions->report(function (Throwable $exception) {
            $request = request();

            if ($request->is('api/doctor/*', 'api/med/*')) {
                ErrorLogger::fromException($exception, $request);
            }
        });

        $exceptions->render(
            function (
                ValidationException $exception,
                Request $request
            ) {
                if ($request->is('api/*')) {
                    $errors = $exception->errors();

                    $firstError = collect($errors)
                        ->flatten()
                        ->first();

                    return ApiResponse::validationError(
                        message: $firstError ?? 'Validation failed.',
                        data: $errors
                    );
                }
            }
        );
    })->create();
