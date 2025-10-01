<?php

namespace App\Services;

use Carbon\Carbon;
use App\Mail\SubscribeReminderMail;
use App\Models\User;
use App\Models\UserPushToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;
use Symfony\Component\Console\Output\ConsoleOutput;

class UserService
{
    /**
     * Get current user
     *
     * @return \App\Models\User
     */
    public static function getCurrentUser()
    {
        return auth()->user();
    }

    /**
     * Get current user ID
     *
     * @return int
     */
    public static function id()
    {
        return auth()->user() ? auth()->user()->id : null;
    }

    /**
     * Check if user is admin
     *
     * @return bool
     */
    public static function isAdmin()
    {
        $user = self::getCurrentUser();
        return $user ? $user->isAdmin() : false;
    }

    public static function sendSubscriptionReminder()
    {
        User::notTerminated()
            ->withoutAdmin()
            ->has('subscriptions')
            ->chunk(50, function ($users) {
                foreach ($users as $user) {
                    if (self::notCurrentlySubscribed($user)) {
                        Mail::to($user->email)->send(new SubscribeReminderMail($user));
                    }
                }
            });
    }

    public static function updateStatusesOfUsers()
    {
        User::notTerminated()
            ->withoutAdmin()
            ->chunk(50, function ($users) {
                foreach ($users as $user) {
                    if (self::eligibleForUnsubscription($user)) {
                        $user->update([
                            'user_status' => User::STATUS_UNSUBSCRIBED
                        ]);
                    } else {
                        $user->update([
                            'user_status' => User::STATUS_SUBSCRIBED
                        ]);
                    }
                }
            });
    }

    public static function forceRemoveUnsubscribeUserFiles()
    {
        $days = env('THRESHOLD_DELETE_DAYS', 30);
        $lastCreated = now()->subDays($days);

        User::withoutAdmin()
            ->where('created_at', '<', $lastCreated)
            ->orderBy('id')
            ->chunk(50, function ($users) {
                foreach ($users as $user) {
                    if (self::notCurrentlySubscribed($user)) {
                        FileService::removeFilesByUserId($user->id);
                        UserService::updateStorage($user, 0);
                    }
                }
            });
    }

    public static function search(
        $searchText,
        $sortBy = 'id',
        $sortDir = 'asc',
        $limit = 50
    ) {
        $query = User::where(function ($q) use ($searchText) {
            $q
                ->orWhere('first_name', 'like', '%' . $searchText . '%')
                ->orWhere('last_name', 'like', '%' . $searchText . '%')
                ->orWhere('mobile', 'like', '%' . $searchText . '%')
                ->orWhere('email', 'like', '%' . $searchText . '%')
                ->orWhere('account_number', 'like', '%' . $searchText . '%')
                ->orWhere('username', 'like', '%' . $searchText . '%')
                ->orWhere('lifebox_email', 'like', '%' . $searchText . '%')
                ->orWhere('user_type', 'like', '%' . $searchText . '%')
                ->orWhere('user_status', 'like', '%' . $searchText . '%')
                ->orWhere('created_at', 'like', '%' . $searchText . '%');
        });
        $query->orderBy($sortBy ?? 'id', $sortDir ?? 'asc');

        return $query->paginate($limit);
    }

    public static function updateStorage(User $user, $size)
    {
        $parseSize = (float)($size);
        $user->update([
            'storage_size' => $user->storage_size + $parseSize
        ]);
        return $user;
    }

    public static function getUserWithStorageDetails()
    {
        $user = UserService::getCurrentUser();
        $user->storage_size = FileService::totalFileSize($user->id);
        $user->profile_picture = Storage::url("profilePictures/{$user->profile_picture}");
        return $user;
    }

    /**
     * Helper function for creating a users
     *
     * @param Illuminate\Support\Collection $users
     * @return null|User
     */
    public static function create(array $data)
    {
        DB::beginTransaction();
        try {
            $user = User::create($data);
            $userDirectory = "/userstorage/" . $user->id;
            Storage::makeDirectory($userDirectory);
            Storage::makeDirectory($userDirectory . '/trash');

            TagService::generateDefaultTags($user);

            $userFolder = FileService::createFolder($user, $user->id);
            FileService::createFolder($user, 'trashed', $userFolder);
            FileService::generateDefaultFolders($user, $userFolder->id);

            DB::commit();
            return $user;
        } catch (\Exception $e) {
            DB::rollBack();
        }
        return null;
    }

    /**
     * store new profile picture on S3 bucket
     *
     * @param  Request $request
     * @return void
     */
    public static function saveProfilePicture($profilePicture)
    {
        $filename = self::makeFileName($profilePicture);
        $profilePicture->storePubliclyAs('profilePictures/', $filename);

        return $filename;
    }

    /**
     * removed profile picture on S3 bucket
     *
     * @param  Request $request
     * @return void
     */
    public static function removeProfilePicture($profilePicture)
    {
        $picPath = "profilePictures/{$profilePicture}";

        if (Storage::exists($picPath)) {
            Storage::delete($picPath);
            return true;
        }

        return false;
    }

    /**
     * Generate random string filename
     *
     * @param  \Illuminate\Http\UploadedFile $file
     * @return string
     */
    public static function makeFileName(UploadedFile $file)
    {
        return join('.', [
            \Keygen\Keygen::alphanum(32)->generate(),
            $file->getClientOriginalExtension()
        ]);
    }

    /**
     * Helper function for checking if given user is currently UNSUBSCRIBED
     * Based on database, Payment and/or In-App Purchased Subscriptions
     *
     * @param \App\Models\User $user
     * @return boolean
     */
    public static function notCurrentlySubscribed(User $user)
    {
        $latestSubscription = $user->subscriptions()->latest()->first();

        if (!$user->subscribed) {
            if ($latestSubscription->fromStripe()) {
                $subscriptionInstance = $user->subscription('default');
                return $subscriptionInstance->cancelled() ||
                    $subscriptionInstance->onGracePeriod() ||
                    $subscriptionInstance->ended();
            }

            return true;
        }

        return false;
    }

    /**
     * Helper function for to verify if user is legitimately Unsubscribed
     *
     * @param \App\Models\User $user
     * @return boolean
     */
    public static function eligibleForUnsubscription(User $user)
    {
        $latestSubscription = $user->subscriptions()->latest()->first();

        if (!$latestSubscription) {
            return true;
        }

        if ($latestSubscription->fromStripe()) {
            $subscriptionInstance = $user->subscription('default');
            return !$user->subscribed('default') ||
                $subscriptionInstance->cancelled() ||
                $subscriptionInstance->onGracePeriod() ||
                $subscriptionInstance->ended();
        } else {
            return !$latestSubscription->in_app_valid && !$latestSubscription->isValidInApp();
        }
    }

    public static function isUserSubscribed(User $user): bool
    {
        $latestSubscription = $user->subscriptions()->latest()->first();

        if (!$latestSubscription) {
            return false;
        }

        if ($user->subscribed) {
            if ($latestSubscription->fromStripe()) {
                return $user->subscribed('default');
            } else {
                return !$latestSubscription->in_app_expired && $latestSubscription->isValidInApp();
            }
        } else {
            return $latestSubscription->fromStripe()
                ? $user->subscription('default')->onGracePeriod()
                : false;
        }
    }

    public static function hasExistingPushToken($push_token, $device_id): bool
    {
        $tokens = UserPushToken::where([
            ['push_token', $push_token],
            ['device_id', $device_id]
        ])->first();
        return isset($tokens);
    }
}
