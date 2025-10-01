<?php

namespace App\Http\Controllers;

use App\Models\FileTag;
use App\Models\FileTagProperty;
use App\Models\TagProperty;
use App\Services\FileService;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class FileTagPropController extends Controller
{

    /**
     * Get Tag Properties of a file
     *
     * @authenticated
     * @group Tags
     * @urlParam fileId int file_id
     * @urlParam tagId int tag_id
     * @param Request $request
     * @param $fileId
     * @param $tagId
     * @return JsonResponse
     */
    public function index(Request $request, $fileId, $tagId)
    {
        $data = FileService::getTagPropertiesByFileId($fileId, $tagId);
        return response()->json($data);
    }

    /**
     * Update/Create a file tag property
     *
     * @authenticated
     * @group Tags
     * @urlParam fileId int required file_id
     * @urlParam tagId int required tag_id
     * @bodyParam name string required tag_property->name
     * @bodyParam type string tag_property->type
     * @bodyParam value string file_tag_property->value
     * @param Request $request
     * @param $fileId
     * @param $tagId
     * @return JsonResponse
     */
    public function store(Request $request, $fileId, $tagId)
    {
        $data = $request->validate([
            'name' => 'required|string|min:1',
            'type' => 'string|min:1',
            'value' => 'nullable|string'
        ]);
        DB::beginTransaction();
        try {
            $ft = FileTag::firstOrCreate([
                'file_id' => $fileId,
                'tag_id' => $tagId
            ]);

            $tp = TagProperty::firstOrCreate([
                'tag_id' => $tagId,
                'name' => $data['name'],
            ], [
                'type' => $data['type'] ?? 'other'
            ]);

            $ftp = FileTagProperty::updateOrCreate([
                'file_tag_id' => $ft->id,
                'tag_property_id' => $tp->id,
            ], [
                'value' => $data['value']
            ]);
            $ftp->load('property');
            DB::commit();

            return response()->json($ftp, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'msg' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Delete a file_tag_property, tagProperty
     *
     * @authenticated
     * @group Tags
     * @urlParam fileId int required file_id
     * @urlParam tagId int required tag_id
     * @urlParam propertyId int required file_tag_property_id
     * @param Request $request
     * @param $fileId
     * @param $tagId
     * @param $propertyId
     * @return JsonResponse
     */
    public function destroy(Request $request, $fileId, $tagId, $propertyId)
    {
        DB::beginTransaction();
        try {
            FileTagProperty::where('tag_property_id', $propertyId)->where('file_tag_id', function ($q) use ($tagId, $fileId) {
                $q->from('file_tag')
                    ->select('id')
                    ->where('file_tag.tag_id', $tagId)
                    ->where('file_tag.file_id', $fileId)
                    ->limit(1);
            })->delete();
            TagProperty::where('id', $propertyId)
                ->where('system_created', false)
                ->delete();
            DB::commit();
            return response()->json([], Response::HTTP_NO_CONTENT);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
