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
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'identify.company' => \App\Http\Middleware\IdentifyCompany::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule) {
        $schedule->command('autotopup:process')->everyTenMinutes();
        $schedule->command('messages:send-scheduled')->everyMinute();
    })
    ->withExceptions(function (Exceptions $exceptions) {
       //
    })->create();
