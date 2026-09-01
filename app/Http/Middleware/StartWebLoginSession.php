<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Symfony\Component\HttpFoundation\Response;

class StartWebLoginSession
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        return app(StartSession::class)->handle(
            $request,
            $next
        );
    }
}
