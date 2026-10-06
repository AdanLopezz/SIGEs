<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
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

    private function registration(array $overrides = []): array
    {
        return array_replace([
            'name' => 'Persona de Prueba',
            'email' => 'persona@example.com',
            'password' => 'Segura!Clave123',
            'password_confirmation' => 'Segura!Clave123',
        ], $overrides);
    }

    public function test_guests_see_the_correct_panel_and_csrf_fields(): void
    {
        $this->get('/login')->assertOk()->assertViewIs('auth.acceso')
            ->assertViewHas('panel', 'login')->assertSee('name="_token"', false);
        $this->get('/register')->assertOk()->assertViewIs('auth.acceso')
            ->assertViewHas('panel', 'register')->assertSee('register-active');
    }

    public function test_registration_normalizes_email_hashes_password_and_starts_a_new_session(): void
    {
        $this->withSession(['pre_auth' => 'value']);
        $sessionId = session()->getId();
        $this->post('/register', $this->registration(['email' => '  PERSONA@Example.COM  ', 'email_verified_at' => now(), 'id' => 999]))
            ->assertRedirect('/dashboard')->assertSessionHasNoErrors();

        $user = User::where('email', 'persona@example.com')->sole();
        $this->assertAuthenticatedAs($user);
        $this->assertTrue(Hash::check('Segura!Clave123', $user->password));
        $this->assertNotSame('Segura!Clave123', $user->password);
        $this->assertNull($user->email_verified_at);
        $this->assertNotSame(999, $user->id);
        $this->assertNotSame($sessionId, session()->getId());
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
        foreach (['/', '/login', '/register'] as $url) {
            $this->get($url)->assertRedirect('/dashboard');
        }
        $this->post('/register', $this->registration())->assertRedirect('/dashboard');
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

    public function test_duplicate_email_after_normalization_is_rejected(): void
    {
        User::factory()->create(['email' => 'persona@example.com']);
        $this->post('/register', $this->registration(['email' => ' PERSONA@EXAMPLE.COM ']))
            ->assertRedirect('/register')->assertSessionHasErrorsIn('register', ['email']);
        $this->assertDatabaseCount('users', 1);
        $this->assertGuest();
    }

    #[DataProvider('invalidRegistrations')]
    public function test_registration_validation(array $overrides, string $field): void
    {
        $this->post('/register', $this->registration($overrides))
            ->assertRedirect('/register')->assertSessionHasErrorsIn('register', [$field]);
        $this->assertDatabaseCount('users', 0);
        $this->assertGuest();
        $this->assertNull(session()->getOldInput('password'));
        $this->assertNull(session()->getOldInput('password_confirmation'));
        $this->get('/register')->assertOk();
    }

    public static function invalidRegistrations(): array
    {
        return [
            'missing name' => [['name' => ''], 'name'],
            'long name' => [['name' => str_repeat('a', 101)], 'name'],
            'array name' => [['name' => ['unexpected']], 'name'],
            'missing email' => [['email' => ''], 'email'],
            'invalid email' => [['email' => 'invalid'], 'email'],
            'long email' => [['email' => str_repeat('a', 250).'@example.com'], 'email'],
            'array email' => [['email' => ['unexpected']], 'email'],
            'missing password' => [['password' => ''], 'password'],
            'array password' => [['password' => ['unexpected']], 'password'],
            'missing confirmation' => [['password_confirmation' => ''], 'password'],
            'wrong confirmation' => [['password_confirmation' => 'different'], 'password'],
            'short password' => [['password' => 'Aa1!', 'password_confirmation' => 'Aa1!'], 'password'],
            'weak password' => [['password' => 'abcdefghijklmnop', 'password_confirmation' => 'abcdefghijklmnop'], 'password'],
            'bcrypt byte limit' => [['password' => str_repeat('á', 36).'Aa1!', 'password_confirmation' => str_repeat('á', 36).'Aa1!'], 'password'],
        ];
    }

    public function test_login_validation_and_registration_errors_do_not_leak_between_panels(): void
    {
        $this->post('/register', $this->registration(['password_confirmation' => 'wrong']))
            ->assertSessionHasErrorsIn('register', ['password']);
        $page = $this->get('/register')->assertOk()->assertViewHas('panel', 'register');
        $this->assertSame(1, substr_count($page->getContent(), 'value="persona@example.com"'));
        $this->assertFalse($page->viewData('errors')->getBag('login')->any());

        $this->post('/login', ['email' => 'not-email', 'password' => ''])
            ->assertRedirect('/login')->assertSessionHasErrorsIn('login', ['email', 'password']);
        $page = $this->get('/login')->assertOk()->assertViewHas('panel', 'login');
        $this->assertSame(1, substr_count($page->getContent(), 'value="not-email"'));
        $this->assertFalse($page->viewData('errors')->getBag('register')->any());
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

    public function test_consecutive_failed_posts_replace_the_previous_form_errors(): void
    {
        $this->post('/register', $this->registration(['name' => '']))->assertSessionHasErrorsIn('register', ['name']);
        $this->post('/login', ['email' => 'login@example.com', 'password' => 'wrong'])->assertSessionHasErrorsIn('login', ['email']);
        $page = $this->get('/login')->assertOk();
        $this->assertFalse($page->viewData('errors')->getBag('register')->any());
        $this->assertSame(1, substr_count($page->getContent(), 'value="login@example.com"'));
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
        $this->post('/register', $this->registration())->assertStatus(419);
        $this->actingAs(User::factory()->create())->post('/logout')->assertStatus(419);
        $this->withSession(['_token' => 'valid-csrf-token'])->post('/logout', ['_token' => 'valid-csrf-token'])->assertRedirect('/login');
    }

    public function test_routes_are_unique_and_out_of_scope_features_are_absent(): void
    {
        $routes = collect(Route::getRoutes()->getRoutes());
        foreach (['login', 'login.store', 'register', 'register.store', 'logout', 'dashboard'] as $name) {
            $this->assertCount(1, $routes->filter(fn ($route) => $route->getName() === $name));
        }
        foreach (['password.request', 'password.reset', 'verification.notice', 'password.confirm', 'two-factor.login', 'passkey.login'] as $name) {
            $this->assertFalse(Route::has($name));
        }
    }
}
