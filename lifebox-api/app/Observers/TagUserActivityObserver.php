<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Tag;
use Illuminate\Support\Facades\Auth;

class TagUserActivityObserver extends BaseUserActivityObserver
{
    const ACTIVITY_TAG_CREATED = 'Created a tag. Tagname: %s, Tag description: %s';
    const ACTIVITY_TAG_DELETED = 'Deleted a tag. Tagname: %s, Tag description: %s';

    public function created(Tag $tag): void
    {
        $this->createUserActivity(
            Auth::user(),
            sprintf(
                self::ACTIVITY_TAG_CREATED,
                $tag->tag_name,
                $tag->tag_description
            )
        );
    }

    public function deleted(Tag $tag): void
    {
        $this->createUserActivity(
            Auth::user(),
            sprintf(
                self::ACTIVITY_TAG_DELETED,
                $tag->tag_name,
                $tag->tag_description
            )
        );
    }
}
