<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use App\Services\TagService;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

use Symfony\Component\Console\Output\ConsoleOutput;

class TagController extends Controller
{
    /**
     * List tags
     *
     * Paginated listing of tags, searchable.
     *
     * @authenticated
     * @group Tags
     * @queryParam limit int number of tags to fetch, default to 50
     * @queryParam sort_by string field to sort by, defaults to 'id'
     * @queryParam sort_dir string sort direction, defaults to 'asc'
     * @queryParam search_text string tag_name, description to search for
     * @queryParam user_id int not_used?
     * @queryParam system string not_used?
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $limit = $request->get('limit', 50);
        $sortBy = $request->get('sort_by', 'id');
        $sortDir = $request->get('sort_dir', 'asc');
        $searchText = $request->get('search_text');
        $userId = $request->get('user_id');
        $system = $request->get('system');

        $rs = TagService::search(
            $searchText,
            $system,
            $userId,
            $sortBy,
            $sortDir,
            $limit
        );

        return response()->json($rs);
    }

    /**
     * Create a tag
     *
     * @authenticated
     * @group Tags
     * @bodyParam tag_name string required
     * @bodyParam tag_description string
     * @bodyParam user_id int User->id
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        $data  = $request->validate(
            [
                'tag_name' => 'required|string|min:1',
                'tag_description' => 'string',
                'user_id' => 'exists:users,id',
                'tag_type_id' => 'nullable|integer',
                'is_outside_tag' => 'nullable|boolean',
            ]
        );

        $tag = TagService::create($data);
        return response()->json($tag, Response::HTTP_CREATED);
    }

    /**
     * Show a tag
     *
     * @authenticated
     * @group Tags
     * @urlParam tag_id int Tag
     * @param Tag $tag
     * @return JsonResponse
     */
    public function show(Tag $tag)
    {
        abort_if(
            !UserService::isAdmin() && UserService::id() != $tag->user_id,
            Response::HTTP_NOT_FOUND,
        );

        return response()->json($tag);
    }

    /**
     * Update tag.
     *
     * @authenticated
     * @group Tags
     * @urlParam tag_id int Tag
     * @bodyParam tag_name string required
     * @bodyParam tag_description string
     * @bodyParam user_id int User->id
     * @param Request $request
     * @return JsonResponse
     */
    public function update(Request $request, Tag $tag)
    {
        abort_if(
            !UserService::isAdmin() && UserService::id() != $tag->user_id,
            Response::HTTP_NOT_FOUND,
        );

        $data = $request->validate(
            [
                'tag_name' => 'required|string',
                'tag_description' => 'string',
                'user_id' => 'exists:users,id'
            ]
        );

        $updatedTag = TagService::update($data, $tag);
        return response()->json($updatedTag);
    }

    /**
     * Remove the tag
     *
     * @authenticated
     * @group Tags
     * @urlParam tag_id Tag
     * @param Tag $tag
     * @return JsonResponse
     */
    public function destroy(Tag $tag)
    {
        abort_if(
            !UserService::isAdmin() && UserService::id() != $tag->user_id,
            Response::HTTP_NOT_FOUND,
        );

        $tag->delete();
        return response()->json([], Response::HTTP_NO_CONTENT);
    }
}
