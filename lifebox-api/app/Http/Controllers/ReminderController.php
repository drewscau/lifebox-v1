<?php

namespace App\Http\Controllers;

use App\Models\Reminder;
use App\Services\ReminderService;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ReminderController extends Controller
{
    /**
     * List/search reminders
     *
     * @authenticated
     * @group Reminders
     * @queryParam limit int defaults to 50
     * @queryParam sort_by string defaults to 'id'
     * @queryParam sort_dir string defaults to 'asc'
     * @queryParam search_text string
     * @queryParam user_id int
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $limit = $request->query('limit', 50);
        $sortBy = $request->query('sort_by', 'id');
        $sortDir = $request->query('sort_dir', 'asc');
        $searchText = $request->query('search_text');
        $userId = $request->query('user_id');

        $rs = ReminderService::search(
            $searchText,
            $userId,
            $sortBy,
            $sortDir,
            $limit
        );

        return response()->json($rs);
    }

    /**
     * Create a reminder
     *
     * @authenticated
     * @group Reminders
     * @bodyParam reminder_name string required
     * @bodyParam reminder_description string
     * @bodyParam due_date_time string date
     * @bodyParam file_id int required
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        $user = UserService::getCurrentUser();
        $data = $request->validate([
            'reminder_name' => 'required|string|min:3',
            'reminder_description' => 'string',
            'due_date_time' => 'required',
            'file_id' => 'required|exists:files,id',
        ]);
        $data['user_id'] = $user->id;
        $reminder = Reminder::create($data);
        $reminder->load('file');

        return response()->json($reminder, Response::HTTP_CREATED);
    }

    /**
     * Show a reminder
     *
     * @authenticated
     * @group Reminders
     * @urlParam id int required reminder_id
     * @param $id
     * @return JsonResponse
     */
    public function show($id)
    {
        $reminder = ReminderService::getById($id);
        $reminder->load('file');
        return response()->json($reminder);
    }

    /**
     * Update a reminder
     *
     * @authenticated
     * @group Reminders
     * @urlParam id int required reminder_id
     * @bodyParam reminder_name string required
     * @bodyParam reminder_description string
     * @bodyParam due_date_time string date
     * @bodyParam file_id int required
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function update(Request $request, $id)
    {
        $reminder = ReminderService::getById($id);
        $data = $request->validate([
            'reminder_name' => 'string|min:3',
            'reminder_description' => 'string',
            'due_date_time' => 'nullable',
            'file_id' => 'exists:files,id',
        ]);
        $reminder->update($data);

        return response()->json($reminder);
    }

    /**
     * Delete a reminder
     *
     * @authenticated
     * @group Reminders
     * @urlParam id int required reminder_id
     * @param $id
     * @return JsonResponse
     */
    public function destroy($id)
    {
        $reminder = ReminderService::getById($id);
        $reminder->delete();
        return response()->json([], Response::HTTP_NO_CONTENT);
    }
}
