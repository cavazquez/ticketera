<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_is_hidden_by_default(): void
    {
        $this->get('/register')->assertNotFound();
    }

    public function test_registration_screen_can_be_rendered_when_enabled(): void
    {
        Setting::current()->update(['allow_public_registration' => true]);

        $this->get('/register')->assertOk();
    }

    public function test_new_users_can_register_when_enabled(): void
    {
        Setting::current()->update(['allow_public_registration' => true]);

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }
}
