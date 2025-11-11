<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
// ⬇️ เพิ่ม use ของ Sanctum และ (แนะนำ) SubstituteBindings
use Illuminate\Routing\Middleware\SubstituteBindings;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api/',   // ⬅️ สังเกต: API ของคุณอยู่ภายใต้ /api/v1
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        /**
         * ทำให้คำขอจาก FE (localhost:9000) ถูกมองเป็น "stateful"
         * เพื่อให้ส่ง/รับคุกกี้ session + CSRF กับ Sanctum ได้
         * ควรอยู่ในกลุ่ม 'api'
         */
        $middleware->appendToGroup('api', EnsureFrontendRequestsAreStateful::class);

        /**
         * (แนะนำ) ให้แน่ใจว่า api group มี SubstituteBindings
         * เพื่อให้ Route Model Binding / implicit bindings ทำงานครบ
         * ถ้ามีอยู่แล้วจะไม่มีผลซ้ำซ้อน
         */
        $middleware->appendToGroup('api', SubstituteBindings::class);

        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return null;
            }

            return config('app.front_login_url', '/auth/login');
        });

        // หมายเหตุ:
        // - HandleCors ถูกจัดการอัตโนมัติผ่าน config/cors.php
        // - กลุ่ม 'web' มี session/cookie/CSRF อยู่แล้ว ไม่ต้องแตะ
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();
