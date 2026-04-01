<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
        $response->assertSee('Reset Password', false);
        $response->assertSee('captcha', false);
    }

    public function test_login_screen_contains_forgot_password_link(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Lupa Password?', false);
        $response->assertSee(route('password.request', [], false), false);
    }

    public function test_reset_password_requires_valid_captcha_before_redirecting_to_reset_form(): void
    {
        $user = User::factory()->create([
            'email' => 'reset@example.com',
            'is_active' => true,
        ]);

        $this->get('/forgot-password');
        $captcha = session('password_reset_captcha_answer');

        $response = $this->post('/forgot-password', [
            'email' => $user->email,
            'captcha_answer' => $captcha,
        ]);

        $response->assertRedirect();
        $this->assertStringStartsWith('/reset-password/', parse_url($response->headers->get('Location'), PHP_URL_PATH) ?? '');
        $this->assertDatabaseHas('password_reset_tokens', ['email' => $user->email]);
    }

    public function test_reset_password_fails_with_invalid_captcha(): void
    {
        $user = User::factory()->create([
            'email' => 'reset2@example.com',
            'is_active' => true,
        ]);

        $this->get('/forgot-password');

        $response = $this->from('/forgot-password')->post('/forgot-password', [
            'email' => $user->email,
            'captcha_answer' => '999',
        ]);

        $response->assertRedirect('/forgot-password');
        $response->assertSessionHasErrors('captcha_answer');
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        $user = User::factory()->create([
            'email' => 'render@example.com',
            'is_active' => true,
        ]);

        $this->get('/forgot-password');
        $captcha = session('password_reset_captcha_answer');

        $response = $this->post('/forgot-password', [
            'email' => $user->email,
            'captcha_answer' => $captcha,
        ]);

        $response->assertRedirect();
        $location = $response->headers->get('Location');

        $screen = $this->get($location);
        $screen->assertStatus(200);
        $screen->assertSee('Buat Password Baru', false);
    }

    public function test_password_can_be_reset_with_valid_token_and_captcha(): void
    {
        $user = User::factory()->create([
            'email' => 'valid-reset@example.com',
            'password' => 'old-password',
            'is_active' => true,
        ]);

        $this->get('/forgot-password');
        $captcha = session('password_reset_captcha_answer');

        $response = $this->post('/forgot-password', [
            'email' => $user->email,
            'captcha_answer' => $captcha,
        ]);

        $location = $response->headers->get('Location');
        $token = basename((string) parse_url($location, PHP_URL_PATH));

        $this->get($location);
        $confirmCaptcha = session('password_reset_confirm_captcha_answer');

        $resetResponse = $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'password-baru',
            'password_confirmation' => 'password-baru',
            'captcha_answer' => $confirmCaptcha,
        ]);

        $resetResponse
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('password-baru', $user->fresh()->password));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
    }

    public function test_reset_password_requires_valid_confirmation_captcha(): void
    {
        $user = User::factory()->create([
            'email' => 'invalid-confirm@example.com',
            'is_active' => true,
        ]);

        $plainToken = 'plain-token';
        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => Hash::make($plainToken),
            'created_at' => now(),
        ]);

        $this->get('/reset-password/'.$plainToken.'?email='.urlencode($user->email));

        $response = $this->from('/reset-password/'.$plainToken.'?email='.urlencode($user->email))
            ->post('/reset-password', [
                'token' => $plainToken,
                'email' => $user->email,
                'password' => 'password-baru',
                'password_confirmation' => 'password-baru',
                'captcha_answer' => '000',
            ]);

        $response->assertRedirect('/reset-password/'.$plainToken.'?email='.urlencode($user->email));
        $response->assertSessionHasErrors('captcha_answer');
    }
}
