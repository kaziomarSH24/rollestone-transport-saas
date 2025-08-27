<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'identify.company' => \App\Http\Middleware\IdentifyCompany::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule) {
        $schedule->command('autotopup:process')->everyTenMinutes();
        $schedule->command('messages:send-scheduled')->everyMinute();
        $schedule->command('alerts:schedule-trip-alerts')->dailyAt('03:00');
    })
    ->withExceptions(function (Exceptions $exceptions) {
       //
    })->create();
