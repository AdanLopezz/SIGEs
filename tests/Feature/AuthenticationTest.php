<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        // Never migrate, truncate or refresh the application's PostgreSQL database.
        $this->assertSame('testing', app()->environment());
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->artisan('migrate', ['--force' => true])->assertExitCode(0);
    }

    public function test_guests_see_the_correct_panel_and_csrf_fields(): void
    {
        $this->get('/login')->assertOk()->assertViewIs('auth.acceso')
            ->assertViewHas('panel', 'login')->assertSee('name="_token"', false);
    }

    public function test_valid_login_normalizes_email_and_regenerates_session(): void
    {
        $user = User::factory()->create(['email' => 'persona@example.com', 'password' => 'Segura!Clave123']);
        $this->withSession(['pre_auth' => 'value']);
        $sessionId = session()->getId();

        $this->post('/login', ['email' => ' PERSONA@EXAMPLE.COM ', 'password' => 'Segura!Clave123'])
            ->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($sessionId, session()->getId());
    }

    public function test_invalid_credentials_are_generic_for_existing_and_unknown_users(): void
    {
        User::factory()->create(['email' => 'persona@example.com']);
        foreach (['persona@example.com', 'unknown@example.com'] as $email) {
            $response = $this->post('/login', ['email' => $email, 'password' => 'Incorrecta!123']);
            $response->assertRedirect('/login')->assertSessionHasErrorsIn('login', ['email']);
            $this->assertSame('El correo o la contraseña son incorrectos.', session('errors')->getBag('login')->first('email'));
            $this->assertGuest();
            $this->assertNull(session()->getOldInput('password'));
        }
    }

    public function test_remember_cookie_restores_authentication_without_the_original_session(): void
    {
        $user = User::factory()->create(['password' => 'Segura!Clave123', 'remember_token' => null]);
        $cookieName = Auth::guard('web')->getRecallerName();
        $response = $this->post('/login', ['email' => $user->email, 'password' => 'Segura!Clave123', 'remember' => '1']);
        $response->assertRedirect('/dashboard')->assertCookie($cookieName);
        $this->assertNotEmpty($user->fresh()->remember_token);
        $cookie = $response->getCookie($cookieName, false);

        session()->invalidate();
        Auth::forgetGuards();
        $this->withUnencryptedCookie($cookieName, $cookie->getValue())
            ->get('/dashboard')->assertOk();
        $this->assertAuthenticatedAs($user);
        $this->assertTrue(Auth::guard('web')->viaRemember());
    }

    public function test_login_without_remember_does_not_issue_a_recaller_cookie(): void
    {
        $user = User::factory()->create(['password' => 'Segura!Clave123']);
        $this->post('/login', ['email' => $user->email, 'password' => 'Segura!Clave123'])
            ->assertRedirect('/dashboard')->assertCookieMissing(Auth::guard('web')->getRecallerName());
    }

    public function test_dashboard_requires_auth_and_displays_only_escaped_identity(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $user = User::factory()->create(['name' => '<script>alert(1)</script>']);
        $this->actingAs($user)->get('/dashboard')->assertOk()
            ->assertSee($user->name)->assertDontSee($user->name, false)
            ->assertSee($user->email)->assertSee('Cerrar sesión')->assertSee('name="_token"', false);
    }

    public function test_authenticated_users_cannot_access_guest_forms(): void
    {
        $this->actingAs(User::factory()->create());
        foreach (['/', '/login'] as $url) {
            $this->get($url)->assertRedirect('/dashboard');
        }
        $this->assertDatabaseCount('users', 1);
    }

    public function test_logout_invalidates_session_rotates_csrf_and_remember_token(): void
    {
        $user = User::factory()->create(['password' => 'Segura!Clave123']);
        $this->post('/login', ['email' => $user->email, 'password' => 'Segura!Clave123', 'remember' => '1']);
        $oldRemember = $user->fresh()->remember_token;
        $this->withSession(['private_data' => 'secret']);
        $sessionId = session()->getId();
        $token = session()->token();

        $this->post('/logout')->assertRedirect('/login')->assertSessionMissing('private_data');
        $this->assertGuest();
        $this->assertNotSame($sessionId, session()->getId());
        $this->assertNotSame($token, session()->token());
        $this->assertNotSame($oldRemember, $user->fresh()->remember_token);
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/logout')->assertStatus(405);
    }

    public function test_login_is_throttled_by_normalized_email_and_ip_then_recovers(): void
    {
        User::factory()->create(['email' => 'persona@example.com', 'password' => 'Segura!Clave123']);
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/login', ['email' => 'persona@example.com', 'password' => 'wrong'])->assertRedirect('/login');
        }
        $this->post('/login', ['email' => ' PERSONA@EXAMPLE.COM ', 'password' => 'Segura!Clave123'])
            ->assertStatus(429)->assertHeader('Retry-After')->assertSee('Demasiados intentos');
        $this->assertGuest();
        $this->post('/login', ['email' => 'other@example.com', 'password' => 'wrong'])->assertRedirect('/login');
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.20'])
            ->post('/login', ['email' => 'persona@example.com', 'password' => 'wrong'])->assertRedirect('/login');
        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1']);
        $this->travel(61)->seconds();
        $this->post('/login', ['email' => 'persona@example.com', 'password' => 'wrong'])->assertRedirect('/login');
    }

    public function test_csrf_protects_every_authentication_post(): void
    {
        // Laravel skips CSRF in tests by default. Enable the real check for this test.
        $this->app->bind(ValidateCsrfToken::class, function ($app) {
            return new class($app, $app['encrypter']) extends ValidateCsrfToken
            {
                protected function runningUnitTests(): bool
                {
                    return false;
                }
            };
        });
        $this->post('/login', [])->assertStatus(419);
        $this->actingAs(User::factory()->create())->post('/logout')->assertStatus(419);
        $this->withSession(['_token' => 'valid-csrf-token'])->post('/logout', ['_token' => 'valid-csrf-token'])->assertRedirect('/login');
    }

    public function test_routes_are_unique_and_out_of_scope_features_are_absent(): void
    {
        $routes = collect(Route::getRoutes()->getRoutes());
        foreach (['login', 'login.store', 'logout', 'dashboard'] as $name) {
            $this->assertCount(1, $routes->filter(fn ($route) => $route->getName() === $name));
        }
        foreach (['password.request', 'password.reset', 'verification.notice', 'password.confirm', 'two-factor.login', 'passkey.login'] as $name) {
            $this->assertFalse(Route::has($name));
        }
    }
}
