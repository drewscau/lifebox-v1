<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\FileTag;
use App\Models\FileTagProperty;
use App\Services\FileService;
use App\Services\TagService;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class FileTagController extends Controller
{
    /**
     * Get tags of a file
     *
     * @authenticated
     * @group Tags
     * @urlParam fileId int file_id
     * @return JsonResponse
     */
    public function index($fileId)
    {
        $tags = TagService::getTagsByFileId($fileId);
        return response()->json($tags);
    }

    /**
     * Tag a file
     *
     * @authenticated
     * @group Tags
     * @urlParam fileId int file_id
     * @bodyParam tag_name string required
     * @bodyParam tag_description string
     * @bodyParam user_id int users id
     * @param Request $request
     * @param int $fileId
     * @return JsonResponse
     */
    public function store(Request $request, $fileId)
    {
        $data  = $request->validate([
            'tag_name' => 'required|string|min:1',
            'tag_description' => 'string',
            'user_id' => 'exists:users,id'
        ]);

        $tag = TagService::create($data);
        $file = FileService::getById($fileId);
        $file->tags()->attach($tag->id);
        $tag->load('files');
        return response()->json($tag, Response::HTTP_CREATED);
    }

    /**
     * Update a files tag
     *
     * @authenticated
     * @group Tags
     * @param Request $request
     * @param $fileId
     * @param $tagId
     * @return JsonResponse
     */
    public function update(Request $request, $fileId, $tagId)
    {
        $file = FileService::getById($fileId);
        $file->tags()->syncWithoutDetaching($tagId);
        $file->load([
            'tags' => function ($q) use ($tagId) {
                $q->where('tags.id', $tagId);
            }
        ]);

        return response()->json($file, Response::HTTP_ACCEPTED);
    }

    /**
     * Delete a file_tag_property
     *
     * @authenticated
     * @group Tags
     * @urlParam fileId int required file_id
     * @urlParam tagId int required tag_id
     * @param $fileId
     * @param $tagId
     * @return JsonResponse
     */
    public function destroy($fileId, $tagId)
    {
        $file = File::findOrFail($fileId);
        abort_if(
            !UserService::isAdmin() && UserService::id() != $file->user_id,
            Response::HTTP_NOT_FOUND,
        );
        FileTagProperty::whereIn(
            'file_tag_id',
            function ($q) use ($fileId, $tagId) {
                $q->from('file_tag')
                    ->select('id')
                    ->where('file_tag.file_id', $fileId)
                    ->where('file_tag.tag_id', $tagId);
            }
        )->delete();
        foreach (FileTag::where('tag_id', $tagId)->where('file_id', $fileId)->get() as $fileTag) {
            $fileTag->delete();
        }
        return response()->json([], Response::HTTP_NO_CONTENT);
    }
}
