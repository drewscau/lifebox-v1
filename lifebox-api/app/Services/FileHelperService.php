<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\User;

class FileHelperService
{
    public static function isMoreThanUserStorage(User $user, float $newFileSize): bool
    {
        return $user->storage_limit <= (FileService::totalFileSize($user->id) + $newFileSize);
    }
}
