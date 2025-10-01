<?php

namespace App\Services;

use App\Models\Reminder;

class ReminderService
{
    public static function search($searchText, $userId = null, $sortBy = 'id', $sortDirection = 'asc', $limit = 50)
    {
        $query = Reminder::query();
        if (!UserService::isAdmin()) {
            $userId = UserService::id();
        }
        $query->where('user_id', $userId);
        if ($searchText) {
            $query->where(function ($q) use ($searchText) {
                $q
                    ->orWhere('reminder_name', 'like', '%' . $searchText . '%')
                    ->orWhere('reminder_description', 'like', '%' . $searchText . '%')
                    ->orWhere('due_date_time', 'like', '%' . $searchText . '%');
            });
        }

        $query->orderBy($sortBy, $sortDirection);

        return $query->paginate($limit);
    }

    public static function getById($id)
    {
        $query = Reminder::query();
        if (!UserService::isAdmin()) {
            $query->where('user_id', UserService::id());
        }
        return $query->where('id', $id)->firstOrFail();
    }
}
