<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\FileTag;
use Illuminate\Support\Facades\Auth;

class FileTagUserActivityObserver extends BaseUserActivityObserver
{
    const ACTIVITY_FILE_TAG_CREATED = 'Tagged a file. Filename: %s, Tagname: %s';
    const ACTIVITY_FILE_TAG_DELETED = 'Removed a tag. Filename: %s, Tagname: %s';

    public function created(FileTag $fileTag): void
    {
        $this->createUserActivity(
            Auth::user(),
            sprintf(
                self::ACTIVITY_FILE_TAG_CREATED,
                $fileTag->file()->first()->file_name,
                $fileTag->tag()->first()->tag_name
            )
        );
    }

    public function deleted(FileTag $fileTag): void
    {
        $this->createUserActivity(
            Auth::user(),
            sprintf(
                self::ACTIVITY_FILE_TAG_DELETED,
                $fileTag->file()->first()->file_name,
                $fileTag->tag()->first()->tag_name
            )
        );
    }
}
