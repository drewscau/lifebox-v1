<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Tag;
use App\Models\File;
use App\Models\TagProperty;
use App\Services\TagService;
use App\Services\FileService;
use App\Services\UserService;

class updateUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command that updates all or specific users data on the database (depends on the executed code on handle())';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // $this->fillInMissingTagsAndFolders();
        UserService::updateStatusesOfUsers();

        /**
         * Insert your other functions here
         */

        $this->info("Update Users Command Has Been Executed Successfully...");
    }

    /**
     * Helper function for filling-in missing tags, tags properties and default foiders for each user in the database
     * Removes globally-defined tags (if there's any)
     */
    public function fillInMissingTagsAndFolders() {
        $users = User::withoutAdmin()->get();
        
        foreach ($users as $user) {
            $defaultTags = Tag::DEFAULTS;
            $userRootFolder = FileService::getUserFolder($user->id, "id");

            foreach ($defaultTags as $tag => $properties) {
                // Generate missing default tags
                $systemTag = Tag::firstOrCreate([
                    'tag_name' => $tag,
                    'tag_description' => $tag,
                    'user_id' => $user->id,
                    'system_created' => true,
                ]);
                
                // Then generate missing default folder based from default tags
                if ($userRootFolder) {
                    File::firstOrCreate([
                        'parent_id' => $userRootFolder,
                        'file_type' => File::FILE_TYPE_FOLDER,
                        'user_id' => $user->id,
                        'file_name' => $tag,
                        'file_status' => File::FILE_STATUS_ACTIVE,
                        'file_size' => 0,
                    ]);
                }

                // Then generate missing default tags properties
                foreach($properties as $property) {
                    TagProperty::firstOrCreate([
                        'tag_id' => $systemTag->id,
                        'name' => $property,
                        'type' => TagService::getType(strtolower($property)),
                        'system_created' => true,
                    ]);
                }
            }
        }

        // Then remove global system tags
        $globalSystemTags = Tag::systemGenerated()->whereNull('user_id')->get();

        foreach ($globalSystemTags as $tag) {
            $tag->properties()->delete();
            $tag->delete();
        }
    }
}
