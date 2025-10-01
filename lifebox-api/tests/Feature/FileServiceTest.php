<?php

namespace Tests\Feature;

use App\Models\File;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;
use Tests\TestCase;

class FileServiceTest extends TestCase
{
    use RefreshDatabase;

    private $subscribedUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subscribedUser = User::factory()
            ->has(\App\Models\Subscription::factory()->count(1))
            ->create(
                [
                    'user_type' => User::USER_TYPE_USER,
                    'user_status' => 'subscribed',
                    'storage_limit' => 1024,
                    'storage_size' => 0.1,
                ]
            )
        ;
    }

    /**
     * @covers \App\Http\Controllers\FileController::download
     * @covers \App\Services\FileService::downloadFolder
     * @runInSeparateProcess // https://stackoverflow.com/questions/9745080/test-php-headers-with-phpunit
     * @group separateProcess
     */
    public function testDownloadEmptyFolderSuccess()
    {
        $user = User::factory()->create([
            'created_at' => now()->subDays(32)
        ]);
        $base = File::factory()->create([
            'file_type' => File::FILE_TYPE_FOLDER,
            'file_name' => $user->id,
            'user_id' => $user->id
        ]);
        $f = File::factory()->create([
            'parent_id'=> $base->id,
            'file_name'=>'base_sample',
            'file_type'=> File::FILE_TYPE_FOLDER]);
        $this->get('/api/files/'. $f->id .'/download.zip?preview=0')->assertSuccessful();
    }

    public function testCopyEndpointSuccess()
    {
        Storage::fake('userstorage');
        Config::set('filesystems.default', 'userstorage');

        $user = $this->subscribedUser;

        $rootFolder = File::factory()->create([
            'file_type' => File::FILE_TYPE_FOLDER,
            'file_name' => $user->id,
            'user_id' => $user->id
        ]);

        $f = UploadedFile::fake()->create('test.txt', 1, 'text/plain');
        $bills = File::factory()->create([
            'user_id' => $user->id,
            'file_name' => 'bills',
            'file_type' => 'folder',
            'file_size' => 0,
            'file_reference' => '',
        ]);

        $testFile = File::factory()->create([
            'parent_id' => $bills->id,
            'user_id' => $user->id,
            'file_name' => $f->name,
            'file_status' => 'close',
            'file_type' => 'file',
            'file_extension' => $f->extension(),
            'file_size' => 0.20,
            'file_reference' => '/userstorage/1/' . $f->name,
        ]);
        Storage::fake('userstorage')
            ->put('/userstorage/1/' . $f->name, $f->getContent());

        Passport::actingAs($user, ['lifebox']);

        $this->postJson('/api/files/'. $testFile->id .'/copy',
                ['destination_id'=> $rootFolder->id]
            )
            ->assertSuccessful()
        ;

        // copy from testFile to rootFolder
        $this->assertDatabaseHas('files', [
            'parent_id' => $rootFolder->id,
            'file_name' => $f->name
        ]);
        // original
        $this->assertDatabaseHas('files', [
            'parent_id' => $bills->id,
            'file_name' => $f->name
        ]);
    }
}
