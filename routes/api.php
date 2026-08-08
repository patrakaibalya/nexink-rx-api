<?php

use App\Http\Controllers\Api\Auth\DoctorAuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post(
        'doctor/register',
        [DoctorAuthController::class, 'register']
    );
});
