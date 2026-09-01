<?php

use App\Http\Middleware\DoctorTenantMiddleware;
use App\Http\Middleware\MasterAdminMiddleware;
use App\Http\Middleware\MedicineOrganizationMiddleware;
use App\Support\ApiResponse;
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
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
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
