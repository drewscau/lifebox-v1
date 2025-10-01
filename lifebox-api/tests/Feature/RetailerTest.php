<?php

namespace Tests\Feature;

use App\Exceptions\Retailer\DefaultRetailerException;
use App\Models\Retailer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class RetailerTest extends TestCase
{
    use RefreshDatabase;

    private $admin;
    private $user;
    private $headers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['user_type' => User::USER_TYPE_ADMIN]);
        $this->user = User::factory()->create(['user_type' => User::USER_TYPE_USER]);
        $this->headers = ['Accept' => 'application/json', 'Content-Type' => 'application/json'];
    }

    public function test_list_retailer_for_non_admin()
    {
        Passport::actingAs($this->user, ['lifebox']);
        $this->withHeaders($this->headers)
            ->getJson('/api/retailer')
            ->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_list_retailer_for_admin()
    {
        Passport::actingAs($this->admin, ['lifebox']);
        Retailer::factory()->count(3)->create();

        $response = $this->getJson('/api/retailer');

        $response->assertOk();
        $responseJson = json_decode($response->getContent(), true);
        $this->assertCount(3, $responseJson['data']);
    }

    public function test_create_retailer_for_non_admin()
    {
        // for some odd reason, this will not always post json headers
        $this->withHeaders($this->headers)
            ->post('/api/retailer', [])
            ->assertStatus(Response::HTTP_UNAUTHORIZED);

        Passport::actingAs($this->user, ['lifebox']);
        $this->withHeaders($this->headers)
            ->post('/api/retailer', [])
            ->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_create_retailer_with_invalid_input()
    {
        Passport::actingAs($this->admin, ['lifebox']);

        $this->postJson('/api/retailer', [])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJson(['message' => 'The given data was invalid.']);

        // no status
        $this->postJson('/api/retailer', ['company' => 'Test-company'])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJson(['message' => 'The given data was invalid.']);

        // no company
        $this->postJson('/api/retailer', ['status' => Retailer::STATUS_ACTIVE])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJson(['message' => 'The given data was invalid.']);

        // invalid status
        $this->postJson('/api/retailer', ['status' => 'INVALID STATUS'])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJson(['message' => 'The given data was invalid.']);
    }

    public function test_create_retailer_success()
    {
        Passport::actingAs($this->admin, ['lifebox']);

        $this->postJson('/api/retailer', [
                'company' => 'Testers Inc.',
                'status' => Retailer::STATUS_ACTIVE,
            ])
            ->assertStatus(Response::HTTP_CREATED);

        $this->assertDatabaseHas('retailers', [
            'company' => 'Testers Inc.',
            'status' => Retailer::STATUS_ACTIVE,
        ]);
    }

    public function test_delete_retailer_success()
    {
        $retailer = Retailer::factory()->create(['company' => 'Test company', 'status' => Retailer::STATUS_ACTIVE]);

        Passport::actingAs($this->admin, ['lifebox']);

        $this->delete('/api/retailer/' . $retailer->id)
            ->assertStatus(Response::HTTP_NO_CONTENT);

        $this->assertDatabaseCount('retailers', 0);
    }

    /**
     * @expectedException DefaultRetailerException
     */
    public function test_delete_default_retailer_error()
    {
        $retailer = Retailer::factory()->create(
            ['company' => Retailer::DEFAULT_RETAILER_COMPANY, 'status' => Retailer::STATUS_ACTIVE]
        );

        Passport::actingAs($this->admin, ['lifebox']);

        $this->delete('/api/retailer/' . $retailer->id);

        $this->assertDatabaseHas('retailers', [
            'company' => Retailer::DEFAULT_RETAILER_COMPANY
        ]);
    }

    public function test_update_retailer_success()
    {
        $retailer = Retailer::factory()->create(['company' => 'test company', 'status' => Retailer::STATUS_ACTIVE]);
        $data = ['company' => 'Updated company', 'status' => Retailer::STATUS_INACTIVE];

        Passport::actingAs($this->admin, ['lifebox']);

        $this->patchJson('/api/retailer/' . $retailer->id, $data)
            ->assertOk()
            ->assertJson($data);

        $this->assertDatabaseHas('retailers', $data);
    }

    public function test_update_retailer_input_validation()
    {
        $retailer = Retailer::factory()->create();
        $invalidStatus = ['status' => 'invalid status'];

        Passport::actingAs($this->admin, ['lifebox']);

        $this->patchJson('/api/retailer/' . $retailer->id, $invalidStatus)
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $this->assertDatabaseMissing('retailers', $invalidStatus);
    }

    /**
     * @expectedException DefaultRetailerException
     */
    public function test_update_default_retailer_error()
    {
        $retailer = Retailer::factory()->create(
            ['company' => Retailer::DEFAULT_RETAILER_COMPANY, 'status' => Retailer::STATUS_ACTIVE]
        );

        Passport::actingAs($this->admin, ['lifebox']);

        $this->patchJson('/api/retailer/' . $retailer->id, [
                'company' => 'Updated company',
            ]
        );

        $this->assertDatabaseHas('retailers', [
            'company' => Retailer::DEFAULT_RETAILER_COMPANY
        ]);
        $this->assertDatabaseMissing('retailers', [
            'company' => 'Updated company',
        ]);
    }
}
