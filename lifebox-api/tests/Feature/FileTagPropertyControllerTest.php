<?php

namespace Tests\Feature;

use App\Models\File;
use App\Models\FileTag;
use App\Models\FileTagProperty;
use App\Models\Subscription;
use App\Models\Tag;
use App\Models\TagProperty;
use App\Models\User;
use Database\Seeders\TagsTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

use Illuminate\Support\Facades\DB;
use Laravel\Passport\Passport;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class FileTagPropertyControllerTest extends TestCase
{
    use RefreshDatabase;

    private $subscribedUser;

    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        $this->subscribedUser = User::factory()
            ->has(Subscription::factory()->count(1))
            ->create(
                ['user_type' => User::USER_TYPE_USER, 'user_status' => 'subscribed']
            )
        ;
    }

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function TODO_test_get_file_tag_properties_by_id()
    {
        $this->seed(TagsTableSeeder::class);

        $file = File::factory()->create([
            'user_id' => $this->subscribedUser->id
        ]);
        $tag = Tag::find(1);

        $ft = FileTag::create([
            'tag_id' => $tag->id,
            'file_id' => $file->id
        ]);

        foreach ($tag->properties as $prop) {
            FileTagProperty::create([
                'tag_property_id' => $prop->id,
                'file_tag_id' => $ft->id,
                'value' => 'Holcem  - ' . $prop->name
            ]);
        }
        $response = $this->actingAs($this->subscribedUser, 'api')
            ->getJson('/api/files/' . $file->id . '/properties')
        ;
        $response->assertStatus(200);
    }

    /**
     * @covers \App\Http\Controllers\FileTagPropController::store
     */
    public function test_create_file_tag_property()
    {
        $tag = Tag::factory()->create(['user_id' => $this->subscribedUser->id]);
        $file = File::factory()->create(['user_id' => $this->subscribedUser->id]);

        FileTag::factory()->create([
            'tag_id' => $tag->id,
            'file_id' => $file->id,
        ]);

        $tagName = 'test-date-tag';
        $tagValue = now()->toString();

        Passport::actingAs($this->subscribedUser, ['lifebox']);

        $response = $this->post(
                sprintf('/api/files/%s/tags/%s/properties/', $file->id, $tag->id),
                [
                    'name' => $tagName,
                    'type' => 'test',
                    'value' => $tagValue,
                ]
            )
        ;

        $response->assertStatus(Response::HTTP_CREATED);
        $this->assertDatabaseHas('file_tag_properties',
            ['value' => $tagValue]
        );
        $this->assertDatabaseHas('tag_properties',
            ['name' => $tagName]
        );
    }

    /**
     * @covers \App\Http\Controllers\FileTagPropController::destroy
     */
    public function test_remove_file_tag_properties()
    {
        $user = $this->subscribedUser;

        $file = File::factory()->create(['user_id' => $user->id]);
        $tag = Tag::factory()->create(['user_id' => $user->id]);
        $tagProperty = TagProperty::factory()->for($tag)->create();
        $fileTag = FileTag::factory()->for($tag)->for($file)->create();
        $fileTagProperty = FileTagProperty::factory()
            ->for($fileTag, 'fileTag')
            ->for($tagProperty, 'property')
            ->create(['value' => 'test',])
        ;

        Passport::actingAs($user, ['lifebox']);

        $this->deleteJson(
                sprintf(
                    '/api/files/%s/tags/%s/properties/%s',
                    $file->id,
                    $tag->id,
                    $fileTagProperty->id
                )
            )
            ->assertNoContent()
        ;

        $this->assertDatabaseCount('file_tag_properties', 0);
        $this->assertDatabaseCount('tag_properties', 0);
    }
}
