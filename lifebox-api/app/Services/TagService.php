<?php

namespace App\Services;

use App\Models\Tag;
use App\Models\TagProperty;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TagService
{
    public static function search(
        $searchText,
        $system = null,
        $userId = null,
        $sortBy = 'id',
        $sortDirection = 'asc',
        $limit = 50
    ) {
        $query = Tag::with('properties');
        if ($searchText) {
            $query->where(function ($q) use ($searchText) {
                $q
                    ->orWhere('tag_name', 'like', '%' . $searchText . '%')
                    ->orWhere('tag_description', 'like', '%' . $searchText . '%');
            });
        }

        if (!UserService::isAdmin()) {
            $userId = UserService::id();

            $query->where('user_id', $userId);
            $query->where(function ($q) {
                $q->where('system_created', 1)
                    ->orWhere('system_created', 0);
            });

            // $query->where(function ($q) use ($userId) {
            //     $q->orWhere('user_id', $userId)
            //         ->andWhere('system_created', 1);
            // });
        }

        if (in_array($sortBy, [
            'id',
            'updated_at',
            'created_at',
            'tag_name',
            'tag_description'
        ])) {
            $query->orderBy($sortBy, $sortDirection);
        }

        return $query->paginate($limit);
    }

    public static function getById($id)
    {
        return Tag::findOrFail($id);
    }

    public static function getTagsByFileId($id)
    {
        return Tag::whereHas('files', function ($q) use ($id) {
            $q->where('file_id', $id);
        })->with('properties', function ($q) {
            $q->select(
                'tag_properties.*',
                'tag_properties.name',
                'tag_properties.type',
                'tag_properties.tag_id',
                'file_tag_properties.value',
            )
                ->leftJoin('file_tag_properties', 'tag_properties.id', '=', 'file_tag_properties.tag_property_id')
                ->leftJoin('file_tag', 'file_tag_properties.file_tag_id', '=', 'file_tag.id');
        })
            ->get();
    }

    public static function create(array $data)
    {
        if (!UserService::isAdmin()) {
            $data['user_id'] = UserService::id();
        }
        return Tag::firstOrCreate([
            'tag_name' => $data['tag_name'],
            'user_id' => $data['user_id']
        ], $data);
    }

    public static function update(array $data, Tag $tag)
    {
        if (!UserService::isAdmin()) {
            $data['user_id'] = UserService::id();
        }
        return tap($tag)->update($data);
    }

    public static function generateDefaultTags(User $user)
    {
        $defaultTags = Tag::DEFAULTS;
        foreach ($defaultTags as $tag => $properties) {
            $newSystemTag = Tag::create([
                'tag_name' => $tag,
                'tag_description' => $tag,
                'user_id' => $user->id,
                'system_created' => true,
            ]);

            foreach ($properties as $prop) {
                TagProperty::create([
                    'tag_id' => $newSystemTag->id,
                    'name' => $prop,
                    'type' => self::getType(strtolower($prop)),
                    'system_created' => true,
                ]);
            }
        }
    }

    public static function getType($name)
    {
        if (str_contains($name, 'phone')) {
            return 'phone';
        }
        if (str_contains($name, 'website')) {
            return 'website';
        }
        if (str_contains($name, 'expiry')) {
            return 'date';
        }
        if (str_contains($name, 'date')) {
            return 'date';
        }
        if (str_contains($name, 'purchased')) {
            return 'date';
        }
        if (str_contains($name, 'date')) {
            return 'date';
        }
        if (str_contains($name, 'mobile')) {
            return 'phone';
        }
        if (str_contains($name, 'link')) {
            return 'website';
        }
        return 'other';
    }
}
