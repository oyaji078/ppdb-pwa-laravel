<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessCodeRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_repeated_status_checks_are_throttled(): void
    {
        $this->createPpdbConfiguration();

        $attempt = fn () => $this->post(route('status.authenticate'), [
            'registration_number' => '2601000001',
            'access_code' => 'ABCDEFGH',
        ]);

        // The configured limit is 5 attempts per minute per IP.
        for ($i = 0; $i < 5; $i++) {
            $attempt()->assertStatus(302);
        }

        $attempt()->assertStatus(429);
    }

    public function test_admin_login_attempts_are_throttled(): void
    {
        $admin = $this->createAdmin();

        $attempt = fn () => $this->post(route('admin.login.store'), [
            'username' => $admin->username,
            'password' => 'salah',
        ]);

        for ($i = 0; $i < 5; $i++) {
            $attempt()->assertStatus(302);
        }

        $attempt()->assertStatus(429);
    }
}
