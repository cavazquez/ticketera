<?php

namespace Tests\Feature;

use App\Enums\AuthDriver;
use App\Enums\AuthProvider;
use App\Enums\UserRole;
use App\Models\Setting;
use App\Models\User;
use App\Services\Auth\SsoUserProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class SsoAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_sso_user_is_provisioned_when_auto_provision_enabled(): void
    {
        Setting::current()->update([
            'sso_auto_provision' => true,
            'sso_default_role' => UserRole::Agent->value,
        ]);

        $user = app(SsoUserProvisioner::class)->provision(
            email: 'nuevo@empresa.com',
            name: 'Nuevo Usuario',
            provider: AuthProvider::Keycloak,
            externalId: 'kc-123',
        );

        $this->assertSame('nuevo@empresa.com', $user->email);
        $this->assertSame(UserRole::Agent, $user->role);
        $this->assertSame(AuthProvider::Keycloak, $user->auth_provider);
        $this->assertSame('kc-123', $user->external_id);
    }

    public function test_sso_user_is_rejected_when_auto_provision_disabled(): void
    {
        Setting::current()->update(['sso_auto_provision' => false]);

        $this->expectException(ValidationException::class);

        app(SsoUserProvisioner::class)->provision(
            email: 'desconocido@empresa.com',
            name: 'Desconocido',
            provider: AuthProvider::Ldap,
            externalId: 'uid=desconocido,dc=test,dc=com',
        );
    }

    public function test_existing_user_is_updated_on_sso_login(): void
    {
        $existing = User::factory()->create([
            'email' => 'existente@empresa.com',
            'name' => 'Nombre Viejo',
            'auth_provider' => AuthProvider::Local,
        ]);

        $user = app(SsoUserProvisioner::class)->provision(
            email: 'existente@empresa.com',
            name: 'Nombre Nuevo',
            provider: AuthProvider::Keycloak,
            externalId: 'kc-456',
        );

        $this->assertSame($existing->id, $user->id);
        $this->assertSame('Nombre Nuevo', $user->fresh()->name);
        $this->assertSame(AuthProvider::Keycloak, $user->fresh()->auth_provider);
    }

    public function test_login_page_shows_keycloak_button_when_configured(): void
    {
        Setting::current()->update([
            'auth_driver' => AuthDriver::Keycloak->value,
            'keycloak_base_url' => 'https://auth.test',
            'keycloak_realm' => 'ticketera',
            'keycloak_client_id' => 'ticketera-app',
            'keycloak_client_secret' => 'secret',
        ]);

        $this->get(route('login'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Auth/Login')
                ->where('authDriver', 'keycloak')
                ->where('showLocalLogin', true)
                ->where('keycloakLoginUrl', route('auth.keycloak.redirect'))
            );
    }

    public function test_local_login_blocked_when_keycloak_only(): void
    {
        $user = User::factory()->create();

        Setting::current()->update([
            'auth_driver' => AuthDriver::Keycloak->value,
            'allow_local_login' => false,
            'keycloak_base_url' => 'https://auth.test',
            'keycloak_realm' => 'ticketera',
            'keycloak_client_id' => 'ticketera-app',
            'keycloak_client_secret' => 'secret',
        ]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_keycloak_callback_logs_in_provisioned_user(): void
    {
        Setting::current()->update([
            'auth_driver' => AuthDriver::Keycloak->value,
            'sso_auto_provision' => true,
            'keycloak_base_url' => 'https://auth.test',
            'keycloak_realm' => 'ticketera',
            'keycloak_client_id' => 'ticketera-app',
            'keycloak_client_secret' => 'secret',
        ]);

        $socialiteUser = Mockery::mock(SocialiteUser::class);
        $socialiteUser->shouldReceive('getEmail')->andReturn('keycloak@empresa.com');
        $socialiteUser->shouldReceive('getName')->andReturn('Usuario Keycloak');
        $socialiteUser->shouldReceive('getId')->andReturn('kc-789');

        Socialite::shouldReceive('driver->user')->andReturn($socialiteUser);

        $this->get(route('auth.keycloak.callback'))
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs(
            User::query()->where('email', 'keycloak@empresa.com')->first()
        );
    }
}
