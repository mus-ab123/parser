<?php

namespace Tests\Feature;

use App\Jobs\ScrapeYandexReviewsJob;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class YandexParserTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Junior Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']]);
    }

    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_guest_cannot_access_protected_organization_routes(): void
    {
        $response = $this->getJson('/api/organizations');
        $response->assertStatus(401);
    }

    public function test_user_can_add_valid_yandex_maps_url(): void
    {
        Queue::fake();

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/organizations', [
                'url' => 'https://yandex.ru/maps/org/yandex/1124715036/reviews/',
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['queued' => true])
            ->assertJsonPath('organization.yandex_id', '1124715036');

        $this->assertDatabaseHas('organizations', [
            'yandex_id' => '1124715036',
            'status' => 'pending',
        ]);

        Queue::assertPushed(ScrapeYandexReviewsJob::class);
    }

    public function test_adding_invalid_yandex_url_fails_validation(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/organizations', [
                'url' => 'https://notyandex.ru/maps/something',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['url']);
    }

    public function test_fetching_organization_status(): void
    {
        $org = Organization::create([
            'yandex_id' => '12345',
            'url' => 'https://yandex.ru/maps/org/12345/',
            'status' => 'completed',
            'name' => 'Test Org',
            'rating' => 4.5,
            'review_count' => 10,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/organizations/{$org->id}/status");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'status' => 'completed',
                'name' => 'Test Org',
            ]);
    }
}
