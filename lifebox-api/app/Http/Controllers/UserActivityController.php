<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\UserActivity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserActivityController
{
    /**
     * List all user_activities
     *
     * @authenticated
     * @group Admin
     * @queryParam order_by string defaults to id column
     * @queryParam sort_dir string can be one of: asc, desc. defaults to descending
     * @queryParam limit int rows per page, defaults to 50
     */
    public function index(Request $request): JsonResponse
    {
        $query = UserActivity::query();
        $search = $request->query('search', null);
        if ($search) {
            $query->where('activity', 'like', '%' . $search . '%');
        }
        $query->orderBy(
            $request->query('order_by', 'id'),
            $request->query('sort_dir', 'desc')
        );

        return response()->json($query->paginate($request->query('limit', 50)));
    }

    /**
     * List user_activities of a user
     *
     * @authenticated
     * @group Admin
     * @urlParam userId int required user_id
     * @queryParam order_by string defaults to id column
     * @queryParam sort_dir string can be one of: asc, desc. defaults to descending
     * @param Request $request
     * @param int $userId
     * @return JsonResponse
     */
    public function show(Request $request, int $userId): JsonResponse
    {
        return response()->json(
            UserActivity::where('user_id', $userId)
                ->orderBy(
                    $request->query('order_by', 'id'),
                    $request->query('sort_dir', 'desc')
                )
                ->paginate($request->query('limit', 50))
        );
    }
}
