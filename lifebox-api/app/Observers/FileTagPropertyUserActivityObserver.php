<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\FileTagProperty;
use Illuminate\Support\Facades\Auth;

class FileTagPropertyUserActivityObserver extends BaseUserActivityObserver
{
    const ACTIVITY_FILE_TAG_PROPERTY_CREATED = 'Created file_tag_property. Property: %s, Value: %s, File: %s, Tag: %s';
    const ACTIVITY_FILE_TAG_PROPERTY_UPDATED = 'Updated file_tag_property. Property: %s, Value: %s, File: %s, Tag: %s';

    public function created(FileTagProperty $fileTagProperty): void
    {
        $this->createUserActivity(
            Auth::user(),
            sprintf(
                self::ACTIVITY_FILE_TAG_PROPERTY_CREATED,
                $fileTagProperty->property()->first()->name,
                $fileTagProperty->value,
                $fileTagProperty->fileTag()->first()->file()->first()->file_name,
                $fileTagProperty->fileTag()->first()->tag()->first()->tag_name
            )
        );
    }

    public function updated(FileTagProperty $fileTagProperty): void
    {
        $this->createUserActivity(
            Auth::user(),
            sprintf(
                self::ACTIVITY_FILE_TAG_PROPERTY_UPDATED,
                $fileTagProperty->property()->first()->name,
                $fileTagProperty->value,
                $fileTagProperty->fileTag()->first()->file()->first()->file_name,
                $fileTagProperty->fileTag()->first()->tag()->first()->tag_name
            )
        );
    }
}
