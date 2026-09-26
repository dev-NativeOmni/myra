<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\SampleDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $this->seed(SampleDataSeeder::class);
        $user = User::where('role', User::ROLE_SUPER_ADMIN)->first();

        $response = $this->actingAs($user)->get('/');

        $response->assertStatus(200);
    }
}
