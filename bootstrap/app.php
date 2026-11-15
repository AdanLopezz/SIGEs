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

        // Roles y permisos (Spatie) + bloqueo de cuentas sin rol
        $middleware->alias([
            'role'               => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission'         => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'con.rol'            => \App\Http\Middleware\RequiereRol::class,
            'cambio.contrasena'  => \App\Http\Middleware\DebeCambiarContrasena::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Errores del inicio de sesión: regresan al acceso con el correo y "Recordarme" conservados
        $exceptions->render(function (ValidationException $exception, Request $request) {
            if (! $request->expectsJson() && $request->routeIs('login.store')) {

                $old = array_filter($request->only(['email', 'remember']), 'is_string');

                return redirect()->route('login')
                    ->with('errors', (new ViewErrorBag)->put('login', new MessageBag($exception->errors())))
                    ->withInput($old);
            }

            return null;
        });
    })->create();
