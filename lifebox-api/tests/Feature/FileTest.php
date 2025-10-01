<?php

namespace Tests\Feature;

use App\Models\File;
use App\Models\FileTag;
use App\Models\Tag;
use App\Models\TagProperty;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Passport;
use Tests\TestCase;

class FileTest extends TestCase
{
    use RefreshDatabase;

    private $subscribedUser;

    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        $this->subscribedUser = User::factory()
            ->has(\App\Models\Subscription::factory()->count(1))
            ->create(
                [
                    'user_type' => User::USER_TYPE_USER,
                    'user_status' => 'subscribed',
                    'storage_limit' => 1024,
                ]
            )
        ;
    }

    public function testUploadLimitTo1G()
    {
        $base = File::factory()->create([
            'file_type' => File::FILE_TYPE_FOLDER,
            'file_name' => $this->subscribedUser->id,
            'user_id' => $this->subscribedUser->id
        ]);

        $fileName = 'test-file';
        $testFile = UploadedFile::fake()->create('test.jpg', 0.5 * 1024 * 1024);

        Passport::actingAs($this->subscribedUser, ['lifebox']);

        $this->postJson('/api/files',
                [
                    'parent_id'=> $base->id,
                    'file'=> $testFile,
                    'file_name' => $fileName,
                ]
            )
            ->assertSuccessful()
        ;

        $this->assertDatabaseHas('files',
            [
                'parent_id' => $base->id,
                'file_name' => $fileName,
                'file_type' => 'file',
                'file_extension' => 'jpg',
            ]
        );
    }

    public function testSendMailToUnsubscribeUsers()
    {
        $this->refreshDatabase();
        /** @var User */
        $user = User::factory()->create([
            'created_at' => now()->subDays(32)
        ]);
        DB::table('subscriptions')
            ->insert([
                'user_id' => $user->id,
                'name' => 'default',
                'stripe_id' => 'sub_123123',
                'stripe_status' => 'active',
                'stripe_plan' => 'price_1IITLCKACpsivv2nDMWjGk7e',
                'quantity' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ]);

        File::factory()->create([
            'file_type' => File::FILE_TYPE_FOLDER,
            'file_name' => $user->id,
            'user_id' => $user->id
        ]);
        File::factory()->create([
            'file_type' => File::FILE_TYPE_FOLDER,
            'file_name' => 'trashed',
            'user_id' => $user->id
        ]);

        File::factory(3)->create([
            'user_id' => $user->id
        ]);

        User::factory(5)->create([
            'created_at' => now()->subDays(32)
        ])
            ->each(function ($user) {
                $base = File::factory()->create([
                    'file_type' => File::FILE_TYPE_FOLDER,
                    'file_name' => $user->id,
                    'user_id' => $user->id
                ]);
                File::factory()->create([
                    'file_type' => File::FILE_TYPE_FOLDER,
                    'file_name' => 'trashed',
                    'user_id' => $user->id
                ]);
                File::factory(2)->create([
                    'parent_id' => $base->id,
                    'user_id' => $user->id,
                    'file_type' => File::FILE_TYPE_FILE,
                ]);
                $customFolder = File::factory()->create([
                    'parent_id' => $base->id,
                    'user_id' => $user->id,
                    'file_type' => File::FILE_TYPE_FOLDER,
                ]);
                File::factory(5)->create([
                    'parent_id' => $customFolder->id,
                    'user_id' => $user->id,
                    'file_type' => File::FILE_TYPE_FILE,
                ]);
            });


        UserService::sendSubscriptionReminder();
        $this->assertTrue(true);
    }

    /**
     * @covers \App\Http\Controllers\FileController::clearTrash
     */
    public function testClearTrash()
    {
        $user = $this->subscribedUser;
        $base = File::factory()->create([
            'file_type' => File::FILE_TYPE_FOLDER,
            'file_name' => $user->id,
            'user_id' => $user->id
        ]);
        $trash = File::factory()->create([
            'file_type' => File::FILE_TYPE_FOLDER,
            'file_name' => 'trashed',
            'user_id' => $user->id
        ]);
        $parent1 = File::factory()->create([
            'file_type' => File::FILE_TYPE_FOLDER,
            'file_status' => File::FILE_STATUS_TRASHED,
            'parent_id' => $trash->id,
            'user_id' => $user->id
        ]);
        $parent1parent1 = File::factory()->create([
            'file_type' => File::FILE_TYPE_FOLDER,
            'file_status' => File::FILE_STATUS_TRASHED,
            'parent_id' => $parent1->id,
            'user_id' => $user->id
        ]);
        $parent1parent2 = File::factory()->create([
            'file_type' => File::FILE_TYPE_FOLDER,
            'file_status' => File::FILE_STATUS_TRASHED,
            'parent_id' => $parent1parent1->id,
            'user_id' => $user->id
        ]);
        $parent1parent3 = File::factory()->create([
            'file_type' => File::FILE_TYPE_FOLDER,
            'file_status' => File::FILE_STATUS_TRASHED,
            'parent_id' => $parent1parent2->id,
            'user_id' => $user->id
        ]);
        File::factory(3)->create([
            'file_type' => File::FILE_TYPE_FILE,
            'file_status' => File::FILE_STATUS_TRASHED,
            'parent_id' => $parent1parent3->id,
            'user_id' => $user->id
        ]);
        File::factory(3)->create([
            'file_type' => File::FILE_TYPE_FILE,
            'file_status' => File::FILE_STATUS_TRASHED,
            'parent_id' => $parent1parent2->id,
            'user_id' => $user->id
        ]);
        $f = File::factory()->create([
            'file_type' => File::FILE_TYPE_FILE,
            'file_status' => File::FILE_STATUS_TRASHED,
            'parent_id' => $parent1->id,
            'user_id' => $user->id
        ]);
        File::factory(3)->create([
            'file_type' => File::FILE_TYPE_FILE,
            'file_status' => File::FILE_STATUS_TRASHED,
            'parent_id' => $parent1parent1->id,
            'user_id' => $user->id
        ]);

        $t = Tag::factory()->create([
            'user_id' => $user->id
        ]);
        $tp = TagProperty::create([
            'tag_id' => $t->id,
            'name' => 'sample',
            'type' => 'other',
            'system_created' => false,
        ]);
        File::all()->each(function ($f) use ($t, $tp) {
            $ft = FileTag::factory()->create([
                'file_id' => $f->id,
                'tag_id' => $t->id
            ]);
        });

        Passport::actingAs($user, ['lifebox']);

        $this->deleteJson('/api/files/clear-trash')
            ->assertNoContent();

        // all files of type 'files' deleted
        $this->assertDatabaseMissing(
            'files',
            ['file_type' => File::FILE_TYPE_FILE]
        );

        // only user_folder and trash remain
        $this->assertDatabaseHas(
            'files',
            ['file_type' => File::FILE_TYPE_FOLDER, 'file_name' => 'trashed']
        );
        $this->assertDatabaseHas(
            'files',
            ['file_type' => File::FILE_TYPE_FOLDER, 'file_name' => $user->id]
        );
    }

    /**
     * @covers \App\Http\Controllers\FileController@update
     */
    public function testMove()
    {
        $rootFolder = File::factory()->create([
            'file_type' => File::FILE_TYPE_FOLDER,
            'user_id' => $this->subscribedUser->id,
        ]);
        $destination = File::factory()->create([
            'file_type' => File::FILE_TYPE_FOLDER,
            'user_id' => $this->subscribedUser->id,
        ]);

        $file = File::factory()->create([
            'file_type' => File::FILE_TYPE_FILE,
            'user_id' => $this->subscribedUser->id,
            'parent_id' => $rootFolder->id,
        ]);

        Passport::actingAs($this->subscribedUser, ['lifebox']);

        $this->patch('api/files/' . $file->id, [
                'parent_id' => $destination->id,
            ])
            ->assertSuccessful();

        $this->assertDatabaseHas(
            'files',
            [
                'id' => $file->id,
                'parent_id' => $destination->id,
            ]
        );
    }

    /**
     * @covers \App\Http\Controllers\FileController@destroy
     */
    public function testDelete()
    {
        $file = File::factory()->create([
            'file_name' => 'test-file',
            'user_id' => $this->subscribedUser->id
        ]);

        Passport::actingAs($this->subscribedUser, ['lifebox']);

        $this->delete('/api/files/' . $file->id)
            ->assertNoContent()
        ;

        $this->assertDatabaseMissing('files', ['id' => $file->id]);
    }
}
