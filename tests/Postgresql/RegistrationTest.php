<?php

namespace Tests\Postgresql;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    public function test_fortify_persists_on_the_existing_postgresql_connection_without_leaving_a_user(): void
    {
        $this->assertSame('testing', app()->environment());
        $this->assertSame('pgsql', config('database.default'));
        $this->assertSame('pgsql', DB::connection()->getDriverName());
        $this->withoutVite();
        $email = 'siges-auth-check-'.Str::uuid().'@example.test';
        $connection = DB::connection();
        $connection->beginTransaction();

        try {
            $this->post('/register', [
                'name' => 'Prueba transaccional SIGEs',
                'email' => $email,
                'password' => 'Temporal!Clave123',
                'password_confirmation' => 'Temporal!Clave123',
            ])->assertRedirect('/dashboard')->assertSessionHasNoErrors();

            $user = User::where('email', $email)->sole();
            $this->assertAuthenticatedAs($user);
            $this->assertTrue(Hash::check('Temporal!Clave123', $user->password));
            $this->assertNull($user->email_verified_at);
            $this->assertDatabaseHas('users', ['email' => $email]);
            $this->get('/dashboard')->assertOk()->assertSee($email);
            $this->post('/logout')->assertRedirect('/login');
            $this->assertGuest();
            $this->post('/login', ['email' => $email, 'password' => 'Temporal!Clave123'])->assertRedirect('/dashboard');
            $this->assertAuthenticatedAs($user);
        } finally {
            // Roll back only this test's new row, without DELETE, TRUNCATE or migrations.
            $connection->rollBack();
        }

        $this->assertDatabaseMissing('users', ['email' => $email]);
    }
}
