<?php

namespace App\Providers;

use App\Actions\Fortify\ResetUserPassword;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Support\ViewErrorBag;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Http\Requests\LoginRequest as FortifyLoginRequest;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Las rutas de autenticación se declaran a mano en routes/web.php
        Fortify::ignoreRoutes();

        $this->app->bind(FortifyLoginRequest::class, LoginRequest::class);
    }

    public function boot(): void
    {
        // Pantallas
        Fortify::loginView(fn () => view('auth.acceso'));
        Fortify::requestPasswordResetLinkView(fn () => view('auth.forgot-password'));
        Fortify::resetPasswordView(fn (Request $request) => view('auth.reset-password', ['request' => $request]));

        // Acción que guarda la nueva contraseña
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        // Mensajes en español
        $this->app['translator']->addLines([
            'auth.failed'          => 'El usuario o la contraseña son incorrectos.',
            // Mismo mensaje exista o no la cuenta, y aunque ya se haya enviado uno hace poco: nadie puede averiguar qué cuentas existen.
            'passwords.sent'       => 'Si los datos corresponden a una cuenta, recibirás en tu correo personal un enlace para restablecer tu contraseña.',
            'passwords.user'       => 'Si los datos corresponden a una cuenta, recibirás en tu correo personal un enlace para restablecer tu contraseña.',
            'passwords.throttled'  => 'Si los datos corresponden a una cuenta, recibirás en tu correo personal un enlace para restablecer tu contraseña.',
            'passwords.token'      => 'El enlace no es válido o ya caducó. Solicita uno nuevo.',
            'passwords.reset'      => 'Tu contraseña se actualizó. Ya puedes iniciar sesión.',
        ], $this->app->getLocale());

        // Correo del enlace, en español. Llega al correo personal (User::routeNotificationForMail), nunca al usuario @siges.com.
        ResetPassword::toMailUsing(function ($notifiable, string $token) {
            $url = route('password.reset', ['token' => $token, 'email' => $notifiable->getEmailForPasswordReset()]);

            return (new MailMessage)
                ->subject('SIGEs · Crea o restablece tu contraseña')
                ->greeting('Hola, '.$notifiable->name)
                ->line('Recibimos una solicitud para crear o restablecer la contraseña de tu cuenta en SIGEs.')
                ->line('Tu usuario es: '.$notifiable->email)
                ->action('Crear nueva contraseña', $url)
                ->line('El enlace caduca en '.config('auth.passwords.users.expire').' minutos.')
                ->line('Si no lo solicitaste, ignora este mensaje.')
                ->salutation('Sistema Integral de Gestión Escolar');
        });

        // Límite de intentos de inicio de sesión (sin cambios)
        RateLimiter::for('login', function (Request $request) {
            $email = $request->input('email');
            $key = hash('sha256', (is_string($email) ? Str::lower(trim($email)) : '').'|'.$request->ip());

            return Limit::perMinute(5)->by($key)->response(function (Request $request, array $headers) {
                $message = 'Demasiados intentos. Espera un minuto antes de volver a intentarlo.';

                if ($request->expectsJson()) {
                    return response()->json(['message' => $message], 429, $headers);
                }

                return response()->view('auth.acceso', [
                    'errors' => (new ViewErrorBag)->put('login', new MessageBag(['email' => $message])),
                    'loginEmail' => is_string($request->input('email')) ? $request->input('email') : '',
                    'rememberLogin' => $request->boolean('remember'),
                ], 429, $headers);
            });
        });
    }
}
