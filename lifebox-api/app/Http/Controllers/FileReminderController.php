<?php

namespace App\Http\Controllers;

use App\Models\Reminder;
use App\Services\UserService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class FileReminderController extends Controller
{
    /**
     * Get Notifications
     *
     * @authenticated
     * @group Reminders
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getNotifications()
    {
        $currentTime = Carbon::now();
        $userId = UserService::id();
        $reminders = Reminder::with('file')
            ->whereHas('file')
            ->where('due_date_time', '>', $currentTime)
            ->where('user_id', $userId)
            ->get();
        $notifications = [];
        foreach ($reminders as $reminder) {
            $diffDays = $currentTime->diffInDays($reminder->due_date_time);
            $diffHours = $currentTime->diffInHours($reminder->due_date_time);
            $diffMinutes = $currentTime->diffInMinutes($reminder->due_date_time);
            $fileName = $reminder->file->file_name;
            if ($diffDays > 0 && $diffDays < 31) {
                $notifications[] = "$reminder->reminder_name on $fileName is due in $diffDays day(s)";
            } elseif ($diffHours > 0) {
                $notifications[] = "$reminder->reminder_name on $fileName is due in $diffHours hour(s)";
            } elseif ($diffMinutes > 0) {
                $notifications[] = "$reminder->reminder_name on $fileName is due in $diffMinutes Minute(s)";
            }
        }

        return response()->json($notifications);
    }
}
