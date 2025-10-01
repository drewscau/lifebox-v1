<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\UserActivity;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Log;

abstract class BaseUserActivityObserver
{
    /**
     * @param Authenticatable|null $user
     * @param string $activity
     */
    protected function createUserActivity($user, string $activity)
    {
        try {
            if ($user !== null) {
                UserActivity::create([
                    'user_id' => $user->id,
                    'activity' => $activity
                ]);
            }
        } catch (\Exception $exception) {
            // we don't want the request throwing a 5xx since this is just for logging user activity
            Log::info('Error in creating user_activities record: ' . $exception->getMessage());
        }
    }
}
