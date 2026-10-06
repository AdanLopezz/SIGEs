<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Responses\RegisterResponse;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Support\ViewErrorBag;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Http\Requests\LoginRequest as FortifyLoginRequest;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Fortify::ignoreRoutes();

        $this->app->bind(FortifyLoginRequest::class, LoginRequest::class);
        $this->app->singleton(RegisterResponseContract::class, RegisterResponse::class);
    }

    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);

        Fortify::loginView(fn () => view('auth.acceso', ['panel' => 'login']));
        Fortify::registerView(fn () => view('auth.acceso', ['panel' => 'register']));

        $this->app['translator']->addLines([
            'auth.failed' => 'El correo o la contraseña son incorrectos.',
        ], $this->app->getLocale());

        RateLimiter::for('login', function (Request $request) {
            $email = $request->input('email');
            $key = hash('sha256', (is_string($email) ? Str::lower(trim($email)) : '').'|'.$request->ip());

            return Limit::perMinute(5)->by($key)->response(function (Request $request, array $headers) {
                $message = 'Demasiados intentos. Espera un minuto antes de volver a intentarlo.';

                if ($request->expectsJson()) {
                    return response()->json(['message' => $message], 429, $headers);
                }

                return response()->view('auth.acceso', [
                    'panel' => 'login',
                    'errors' => (new ViewErrorBag)->put('login', new MessageBag(['email' => $message])),
                    'loginEmail' => is_string($request->input('email')) ? $request->input('email') : '',
                    'rememberLogin' => $request->boolean('remember'),
                ], 429, $headers);
            });
        });
    }
}
