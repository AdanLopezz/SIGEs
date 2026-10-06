<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ValidationException $exception, Request $request) {
            if (! $request->expectsJson() && $request->routeIs('login.store', 'register.store')) {
                $form = $request->routeIs('register.store') ? 'register' : 'login';
                $old = array_filter($request->only($form === 'register' ? ['name', 'email'] : ['email', 'remember']), 'is_string');

                return redirect()->route($form)
                    ->with('errors', (new ViewErrorBag)->put($form, new MessageBag($exception->errors())))
                    ->withInput($old);
            }

            return null;
        });
    })->create();
