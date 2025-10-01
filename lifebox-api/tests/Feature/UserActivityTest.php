<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class UserActivityTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['user_type' => User::USER_TYPE_USER]);
        $this->admin = User::factory()->create(['user_type' => User::USER_TYPE_ADMIN]);
    }

    public function test_list_single_user_activity_for_non_admin()
    {
        Passport::actingAs($this->user, ['lifebox']);

        $this->getJson('/api/user-activity/' . $this->user->id)
            ->assertStatus(Response::HTTP_UNAUTHORIZED);

        $this->getJson('/api/user-activity/' . $this->user->id)
            ->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_list_all_user_activity_for_non_admin()
    {
        $this->getJson('/api/user-activity')
            ->assertStatus(Response::HTTP_UNAUTHORIZED);

        Passport::actingAs($this->user, ['lifebox']);

        $this->getJson('/api/user-activity')
            ->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_list_single_user_activity_success()
    {
        UserActivity::factory()
            ->for($this->user)
            ->count(3)
            ->create();

        Passport::actingAs($this->admin, ['lifebox']);

        $this->getJson('/api/user-activity/' . $this->user->id)
            ->assertOk();
    }
}
