<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\TagProperty\InvalidTagAuthorException;
use App\Exceptions\TagProperty\NotUserCreatedTagException;
use App\Models\Tag;
use App\Models\TagProperty;
use App\Services\UserService;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\Console\Output\ConsoleOutput;


class TagPropertyController
{
    use ValidatesRequests;

    /**
     * Add property to a tag
     *
     * Add property to a user created tag
     *
     * @authenticated
     * @group Tags
     * @bodyParam tag_id int required id of tag where property will attach itself
     * @bodyParam property string required name of the property
     * @bodyParam type string type of property, can be one of TagProperty::ALLOWED_TYPES, defaults to 'others'
     * @param Request $request
     * @return JsonResponse
     * @throws InvalidTagAuthorException
     * @throws NotUserCreatedTagException
     */
    public function store(Request $request): JsonResponse
    {
        $out = new ConsoleOutput();
        $out->writeln("Add Tag Property");
        $out->writeln("p1: " . $request->input('property'));
        $out->writeln("t1: " . $request->input('type'));

        $out->writeln("p2: " . $request->property);
        $out->writeln("t2: " . $request->type);
        // $out->writeln(serialize($request));

        $validator = Validator::make($request->all(), [
            'tag_id' => 'required|exists:tags,id',
            'property' => [
                'required',
                'string',
                'regex:/^[-a-zA-Z_0-9\s]+$/i',
                'max:255',
                Rule::unique('tag_properties', 'name')->where(function ($query) use ($request) {
                    return $query
                        ->where('name', $request->property)
                        ->where('tag_id', $request->tag_id);
                })
            ],
            'type' => ['sometimes', Rule::in(TagProperty::ALLOWED_TYPES)]
        ]);

        if ($validator->fails()) {
            $out->writeln(response()->json(
                ['errors' => $validator->errors()],
                Response::HTTP_UNPROCESSABLE_ENTITY
            ));
            return response()->json(
                ['errors' => $validator->errors()],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $tag = Tag::find($request->tag_id);

        if ($tag->system_created) {
            throw new NotUserCreatedTagException('Only user created tags can be modified');
        }

        if ($tag->user_id !== Auth::id() && UserService::isAdmin() === false) {
            throw new InvalidTagAuthorException('Current user does not own the tag or is not an admin.');
        }

        $tagProperty = TagProperty::create([
            'tag_id' => $request->tag_id,
            'name' => $request->property,
            'type' => $request->input('type', TagProperty::TYPE_OTHERS),
            'system_created' => 0,
        ]);

        $out->writeln('Success');
        $out->writeln(response()->json($tagProperty));
        return response()->json($tagProperty);
    }


    /**
     * Get tag properties
     *
     * @authenticated
     * @group Tags
     * @urlParam tag_id int required id of tag where property will attach itself
     * @param Request $request
     * @param int $tagId
     * @return JsonResponse
     */
    public function getProperties(int $tagId): JsonResponse
    {
        $tagProperty = [];
        $tag = Tag::find($tagId);
        if (isset($tag) || !empty($tag)) {
            $tagProperty = $tag->properties()->get();
        }
        return response()->json($tagProperty);
    }


    /**
     * Delete tag property
     *
     * @authenticated
     * @group Tags
     * @urlParam tag_id int required id of tag where property will attach itself
     * @urlParam prop_id int required property id of property that is attached on a tag
     * @param Request $request
     * @param int $tagId
     * @param int $propId
     * @return JsonResponse
     */
    public function removeProperty(int $tagId, int $propId): JsonResponse
    {
        TagProperty::where([
            'tag_id' => $tagId,
            'id' => $propId,
        ])->delete();
        return response()->json([], Response::HTTP_NO_CONTENT);
    }


    /**
     * Update property to a tag
     *
     * Update property to a user created tag
     *
     * @authenticated
     * @group Tags
     * @bodyParam tag_id int required id of tag where property will attach itself
     * @bodyParam property string required name of the property
     * @bodyParam type string type of property, can be one of TagProperty::ALLOWED_TYPES, defaults to 'others'
     * @param Request $request
     * @return JsonResponse
     * @throws InvalidTagAuthorException
     * @throws NotUserCreatedTagException
     */
    public function update(Request $request, int $tagId, int $propId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'property' => [
                'required',
                'string',
                'regex:/^[-a-zA-Z_0-9]+$/i',
                'max:255',
                Rule::unique('tag_properties', 'name')->where(function ($query) use ($request, $tagId, $propId) {
                    return $query
                        ->where('name', $request->property)
                        ->where('tag_id', $tagId)
                        ->where('id', '<>', $propId);
                })
            ],
            'type' => ['sometimes', Rule::in(TagProperty::ALLOWED_TYPES)]
        ]);

        if ($validator->fails()) {
            return response()->json(
                ['errors' => $validator->errors()],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $tag = Tag::find($request->tag_id);

        if ($tag->system_created) {
            throw new NotUserCreatedTagException('Only user created tags can be modified');
        }

        if ($tag->user_id !== Auth::id() && UserService::isAdmin() === false) {
            throw new InvalidTagAuthorException('Current user does not own the tag or is not an admin.');
        }

        $tagProperty = TagProperty::where([
            'tag_id' => $tagId,
            'id' => $propId,
        ]);
        $updatedProperty = tap($tagProperty)->update([
            'name' => $request->property,
            'type' => $request->input('type', TagProperty::TYPE_OTHERS),
        ]);
        return response()->json($updatedProperty);
    }
}
