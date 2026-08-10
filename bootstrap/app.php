<?php

use App\Http\Middleware\DoctorTenantMiddleware;
use App\Http\Middleware\MasterAdminMiddleware;
use App\Http\Middleware\MedicineOrganizationMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
     ->withMiddleware(function ($middleware) {
        $middleware->alias([
            'doctor.tenant' => DoctorTenantMiddleware::class,
            'master_admins' => MasterAdminMiddleware::class,
            'medicine_organizations' => MedicineOrganizationMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
