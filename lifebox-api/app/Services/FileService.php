<?php

namespace App\Services;

use App\Models\File;
use App\Models\FileTag;
use App\Models\FileTagProperty;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class FileService
{

    public static function getFileSize($size)
    {
        return number_format($size / 1024 / 1024, 20);
    }

    public static function getFileSizeOriginal($size)
    {
        return (int)($size * 1024 * 1024);
    }

    public static function downloadFolder($fileId, string $name = null)
    {
        $folder = File::find($fileId);
        $zipName = $name ?? $folder->file_name . '-' . $folder->id;
        $zipFile = public_path($zipName  . '.zip');

        $files = [];
        self::getRecursivelyFiles($folder->id, $folder->file_name, $files);

        $zip = new ZipArchive();
        $zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        if (count($files) == 0) {
            $zip->addEmptyDir('.');
        }

        foreach ($files as $file) {
            $zip->addFromString(
                $file['path'],
                Storage::get($file['link']),
            );
        }

        if (! $zip->close()) {
            abort(Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        @ob_clean();

        header('Content-Type: application/zip');
        header('Content-disposition: attachment; filename=' . $zipName . '.zip');
        header('Content-Length: ' . filesize($zipFile));

        @readfile($zipFile);
        @unlink($zipFile);
    }

    public static function getTagPropertiesByFileId($fileId, $tagId = null)
    {
        $query = DB::table("tag_properties")
            ->select(
                'tag_properties.id',
                'tag_properties.name',
                'tag_properties.type',
                'tag_properties.tag_id',
                'file_tag_properties.value',
                'file_tag_properties.created_at',
                'file_tag_properties.updated_at',
            )
            ->join('file_tag', 'tag_properties.tag_id', '=', 'file_tag.tag_id')
            ->leftJoin(
                'file_tag_properties',
                function ($q) {
                    $q->on(
                        'tag_properties.id',
                        '=',
                        'file_tag_properties.tag_property_id'
                    )->whereColumn('file_tag_properties.file_tag_id', 'file_tag.id');
                }
            )
            ->where('file_tag.file_id', $fileId)
            ->where(function ($q) {

                $q->where('tag_properties.system_created', true);
                $q->orWhereIn('tag_properties.tag_id', function ($q) {
                    $q->from('tags')
                        ->select('tags.id')
                        ->where('tags.user_id', UserService::id());
                });
            });
        if ($tagId) {
            $query->where('tag_properties.tag_id', $tagId);
        }

        return $query->get();
    }

    public static function generateDefaultFolders(User $user, $parentId)
    {
        $defaultFolders = ['INBOX'];
        $defaultTags = $user->tags()->systemGenerated()->get(['tag_name']);

        foreach ($defaultTags as $tag) {
            $defaultFolders[] = $tag->tag_name;
        }

        foreach ($defaultFolders as $folder) {
            File::create([
                'parent_id' => $parentId,
                'file_type' => File::FILE_TYPE_FOLDER,
                'user_id' => $user->id,
                'file_name' => $folder,
                'file_status' => File::FILE_STATUS_ACTIVE,
                'file_size' => 0,
            ]);
        }
    }

    public static function createFolder(User $user, $name, File $parent = null)
    {
        return File::firstOrCreate([
            'user_id' => $user->id,
            'file_name' => $name,
            'file_type' => File::FILE_TYPE_FOLDER,
            'file_status' => File::FILE_STATUS_OPEN,
            'parent_id' => $parent ? $parent->id : null
        ]);
    }

    public static function search(
        $userId = null,
        $searchText,
        $fileName,
        $showContent,
        $showTrashFolder = false,
        $tags = [],
        $status,
        $type = null,
        $trash = null,
        $parentId = null,
        $sortBy = null,
        $sortDirection = null,
        $limit = 50
    ) {
        $query = File::query();
        $queryProperty = File::query();

        if ($fileName && $showContent) {
            $query->whereHas('folder', function ($q) use ($fileName) {
                $q->where('file_name', $fileName);
            });
            $queryProperty->whereHas('folder', function ($q) use ($fileName) {
                $q->where('file_name', $fileName);
            });
        }

        if ($status) {
            $query->where('file_status', $status);
            $queryProperty->where('file_status', $status);

            if ($status == File::FILE_STATUS_OPEN && !$sortBy) {
                $sortBy = 'updated_at';
                $sortDirection = 'DESC';
            }
        }

        if (!$showTrashFolder) {
            $query->where('file_name', '<>', File::FILE_TRASHED);
            $queryProperty->where('file_name', '<>', File::FILE_TRASHED);
        }

        if ($tags) {
            try {
                $tags = json_decode($tags);
            } catch (\Exception $e) {
            }
            $tags = is_array($tags) ? $tags : [$tags];
            $query->whereHas('tags', function ($q) use ($tags) {
                $q->whereIn('tags.id', $tags);
            });
        }

        if (!UserService::isAdmin()) {
            $userId = UserService::id();
        }
        $query->where('files.user_id', $userId);
        $queryProperty->where('files.user_id', $userId);

        if ($type) {
            if ($type === File::FILE_TYPE_FOLDER) {
                $query->folderType();
                $queryProperty->folderType();
            }
            if ($type === File::FILE_TYPE_FILE) {
                $query->fileType();
                $queryProperty->fileType();
            }
        }

        if ($trash != null) {
            $trash = (bool) $trash;
            if ($trash) {
                $query->notTrashed();
                $queryProperty->notTrashed();
            } else {
                $query->trashed();
                $queryProperty->trashed();
            }
        }

        if ($parentId) {
            $query->where('parent_id', $parentId);
            $queryProperty->where('parent_id', $parentId);
        }

        if ($searchText) {
            $searchLike = '%' . $searchText . '%';
            $selectFields = [
                'files.id',
                'files.file_type',
                'files.file_extension',
                'files.user_id',
                'files.file_name',
                'files.file_reference',
                'files.file_status',
                'files.file_size',
                'files.created_at',
                'files.updated_at',
                'files.parent_id'
            ];

            $query->leftJoin('file_tag', 'files.id', '=', 'file_tag.file_id');
            $query->leftJoin('tags', 'file_tag.tag_id', '=', 'tags.id');
            $query->leftJoin(
                'file_tag_properties',
                'file_tag.id',
                '=',
                'file_tag_properties.file_tag_id'
            );
            $query->groupBy('files.id');
            $query->select($selectFields);

            $queryTag = clone $query;
            $queryTag->where('tags.tag_name', 'like', $searchLike);

            $queryProperty->select($selectFields);
            $queryProperty->join('file_tag', 'files.id', '=', 'file_tag.file_id');
            $queryProperty->join('tags', 'file_tag.tag_id', '=', 'tags.id');
            $queryProperty->join(
                'file_tag_properties',
                'file_tag.id',
                '=',
                'file_tag_properties.file_tag_id'
            );

            $queryProperty->groupBy('files.id');

            $dateProperty = DateHelperService::convertToDatabaseDate($searchText);
            if ($dateProperty !== null) {
                $searchLike = '%' . $dateProperty . '%';
            }
            $queryProperty->where('file_tag_properties.value', 'like', $searchLike);

            $sortColumn = $sortBy ? 'files.' . $sortBy : 'files.id';
            $query->orderBy($sortColumn, $sortDirection ?? 'ASC');

            $query->where('file_name', 'like', $searchLike)
                ->union($queryTag)
                ->union($queryProperty);
        }

        return $query->paginate($limit);
    }

    /**
     * @return File
     */
    public static function getById($id, $trash = false, $where = [], $withCounts = [])
    {
        $query = File::query();

        if (!UserService::isAdmin()) {
            $query->where('user_id', UserService::id());
        }

        if (count($withCounts) > 0) {
            $query->withCount($withCounts);
        }

        if (!is_null($trash)) {
            if ($trash) {
                $query->trashed();
            } else {
                $query->notTrashed();
            }
        }

        if ($where && count($where) > 0) {
            $query->where($where);
        }

        return $query->where('id', $id)->firstOrFail();
    }

    public static function getUserFolder($userId = null, $column = false)
    {
        if (!$userId && !UserService::isAdmin()) {
            $userId = UserService::id();
        }
        try {
            $userFolder = File::notTrashed()
                ->where('user_id', $userId)
                ->where('file_name', $userId)
                ->firstOrFail();
        } catch (\Throwable $th) {
            return null;
        }

        if ($column && is_string($column)) {
            return $userFolder->{$column};
        }

        return $userFolder;
    }

    public static function getUserInboxFolder($userId = null)
    {
        if (!UserService::isAdmin()) {
            $userId = UserService::id();
        }
        $userFolder = File::notTrashed()
            ->where('user_id', $userId)
            ->where('file_name', 'INBOX')
            ->first();
        return $userFolder;
    }

    public static function removeById($id)
    {
        $query = File::query();
        if (!UserService::isAdmin()) {
            $query->where('user_id', UserService::id());
        }
        $data = $query->where('id', $id)->firstOrFail();
        $parentId = $data->parent_id;

        FileTagProperty::whereIn('file_tag_id', function ($q) use ($data) {
            $q->from('file_tag')
                ->select('id')
                ->where('file_tag.file_id', $data->id);
        })->delete();

        FileTag::where('file_id', $data->id)->delete();
        File::where('parent_id', $data->id)->delete();
        $data->delete();
        self::updateFolderSize($parentId);
    }

    public static function forceRemoveDueTrashedFiles()
    {
        $days = env('THRESHOLD_DELETE_DAYS', 30);
        $lastUpdated = now()->subDays($days);

        File::trashed()
            ->where('updated_at', '<', $lastUpdated)
            ->orderBy('id')
            ->chunk(50, function ($files) {
                foreach ($files as $file) {
                    if ($file->isFolder()) {
                        self::removeTrashedAllFiles($file->id);
                    }

                    echo "\nDeleting File ID: " . $file->id;

                    $newSize = $file->file_size * -1;

                    FileTagProperty::whereIn('file_tag_id', function ($q) use ($file) {
                        $q->from('file_tag')
                          ->select('id')
                          ->where('file_tag.file_id', $file->id);
                    })->delete();

                    FileTag::where('file_id', $file->id)->delete();

                    if (Storage::exists($file->file_reference)) {
                        Storage::delete($file->file_reference);
                    }

                    UserService::updateStorage($file->user, $newSize);
                    $file->delete();
                }
            });
    }

    public static function removeFilesByUserId($userId)
    {
        File::select('files.*')
            ->whereNotIn('files.file_name', [
                $userId, File::FILE_TRASHED, File::FILE_INBOX
            ])
            ->where('files.user_id', $userId)
            ->orderBy('id')
            ->chunk(50, function ($files) {

                foreach ($files as $file) {
                    if ($file->isFolder()) {
                        self::removeAllFiles($file->id);
                    }
                    $newSize = $file->file_size * -1;
                    FileTagProperty::whereIn('file_tag_id', function ($q) use ($file) {
                        $q->from('file_tag')
                            ->select('id')
                            ->where('file_tag.file_id', $file->id);
                    })->delete();
                    FileTag::where('file_id', $file->id)->delete();
                    if (Storage::exists($file->file_reference)) {
                        Storage::delete($file->file_reference);
                    }
                    $file->delete();
                }
            });
    }

    public static function getTrashedFolder($user_id = null)
    {
        if (!UserService::isAdmin()) {
            $user_id = UserService::id();
        }
        $folder = File::trashedFolder($user_id)->first();
        return $folder;
    }

    public static function getByFolderById($id)
    {
        $query = File::folderType();
        if (!UserService::isAdmin()) {
            $query->where('user_id', UserService::id());
        }
        return $query->where('id', $id)->firstOrFail();
    }

    public static function getByFileById($id)
    {
        $query = File::fileType();
        if (!UserService::isAdmin()) {
            $query->where('user_id', UserService::id());
        }
        return $query->where('id', $id)->firstOrFail();
    }

    public static function move(File $file, File $destination)
    {
        if (
            !UserService::isAdmin() &&
            $file->user_id !== UserService::id() &&
            $destination->user_id !== UserService::id()
        ) {
            throw new \Exception('Not owned!');
        }
        $file->update([
            'parent_id' => $destination->id
        ]);
        self::updateFolderSize($file->folder->id);
        self::updateFolderSize($destination->id);
    }

    public static function isFileExists($parentFile, $fileName)
    {
        $query = File::notTrashed()->fileType()
            ->where('parent_id', $parentFile)
            ->where('file_name', $fileName);
        if (!UserService::isAdmin()) {
            $query->where('user_id', UserService::id());
        }
        return $query->count();
    }

    public static function getFolderByParentId($id)
    {
        $query = File::notTrashed()->folderType()
            ->where('id', $id);
        if (!UserService::isAdmin()) {
            $query->where('user_id', UserService::id());
        }
        return $query->firstOrFail();
    }

    public static function trashed(File $file)
    {
        $trashFolder = self::getTrashedFolder($file->user_id);
        // Added this $parent_id variable to hold the file parent_id, preventing it to mutate which results to not updating the file size
        $parent_id = $file->parent_id;
        /** @var File */
        $trashFile =  tap($file)->update([
            'file_status' => File::FILE_STATUS_TRASHED,
            'parent_id' => $trashFolder->id
        ]);
        if ($trashFile->isFolder()) {
            self::markTrashedAllFiles($trashFile->id);
        }
        self::updateFolderSize($parent_id);
        self::updateFolderSize($trashFolder->id);
        return $trashFile;
    }

    public static function markTrashedAllFiles($folderId)
    {
        File::where('parent_id', $folderId)
            ->orderBy('id')
            ->chunk(50, function ($files) {
                foreach ($files as $file) {
                    if ($file->isFolder()) {
                        self::markTrashedAllFiles($file->id);
                    }
                    $file
                        ->fill(['file_status' => File::FILE_STATUS_TRASHED])
                        ->save();
                }
            });
    }

    public static function markStatusAllFiles($folderId, $status = File::FILE_STATUS_OPEN)
    {
        File::where('parent_id', $folderId)
            ->orderBy('id')
            ->chunk(50, function ($files) use ($status) {
                foreach ($files as $file) {
                    if ($file->isFolder()) {
                        self::markStatusAllFiles($file->id, $status);
                    }
                    $file
                        ->fill(['file_status' => $status])
                        ->save();
                }
            });
    }

    public static function removeTrashedAllFiles($folderId)
    {
        File::where('parent_id', $folderId)
            ->orderBy('id')
            ->chunk(50, function ($files) {
                foreach ($files as $file) {
                    if ($file->isFolder()) {
                        self::removeTrashedAllFiles($file->id);
                    }
                    FileTagProperty::whereIn('file_tag_id', function ($q) use ($file) {
                        $q->from('file_tag')
                            ->select('id')
                            ->where('file_tag.file_id', $file->id);
                    })->delete();
                    FileTag::where('file_id', $file->id)->delete();
                    if (Storage::exists($file->file_reference)) {
                        Storage::delete($file->file_reference);
                    }
                    $file->delete();
                }
            });
    }

    public static function removeAllFiles($folderId)
    {
        File::where('parent_id', $folderId)
            ->orderBy('id')
            ->chunk(50, function ($files) {
                foreach ($files as $file) {
                    if ($file->isFolder()) {
                        self::removeAllFiles($file->id);
                    }
                    FileTagProperty::whereIn('file_tag_id', function ($q) use ($file) {
                        $q->from('file_tag')
                            ->select('id')
                            ->where('file_tag.file_id', $file->id);
                    })->delete();
                    FileTag::where('file_id', $file->id)->delete();
                    if (Storage::exists($file->file_reference)) {
                        Storage::delete($file->file_reference);
                    }
                    $file->delete();
                }
            });
    }

    public static function getRecursivelyFiles($folderId, $baseFolder, &$data = [])
    {
        File::where('parent_id', $folderId)
            ->orderBy('id')
            ->chunk(50, function ($files) use ($baseFolder, &$data) {
                foreach ($files as $file) {
                    if ($file->isFolder()) {
                        self::getRecursivelyFiles($file->id, $baseFolder . '/' . $file->file_name, $data);
                    }
                    if (Storage::exists($file->file_reference)) {
                        array_push($data, [
                            'id' => $file->id,
                            'data' => $file->toArray(),
                            'path' => "$baseFolder/$file->file_name",
                            'link' =>  $file->file_reference
                        ]);
                    }
                }
            });
    }

    public static function totalFolderSize($id, $initialSize = 0)
    {
        $query = File::query();
        if (!UserService::isAdmin()) {
            $query->where('user_id', UserService::id());
        }
        $rs = $query->where('parent_id', $id)
            ->select(DB::raw('sum(files.file_size) as total_filesize'))
            ->groupBy('parent_id')
            ->first();

        return $rs ? ($rs->total_filesize + $initialSize) : 0;
    }

    public static function totalFileSize($userId = null)
    {
        $query = File::query();
        if (!UserService::isAdmin()) {
            $userId = UserService::id();
        }
        $query->where('user_id', $userId);
        $rs = $query
            ->select(DB::raw('sum(files.file_size) as total_filesize'))
            ->where('file_type', File::FILE_TYPE_FILE)
            ->groupBy('files.file_type')
            ->first();

        return $rs ? ($rs->total_filesize) : 0;
    }

    public static function updateFolderSize($id)
    {
        try {
            $file = self::getById($id);
        } catch (ModelNotFoundException $exception) {
            return null;
        }

        if (!$file || !$file->isFolder()) {
            return null;
        }

        $size = self::totalFolderSize($id);
        $file->update([
            'file_size' => $size
        ]);

        return $file;
    }

    public static function copy($sourceId, $destinationId)
    {
        $user = UserService::getCurrentUser();
        $sourceFile = self::getById($sourceId);
        $destinationFile = self::getById($destinationId);

        $sourceFileSize = $sourceFile->isFolder() ? self::totalFolderSize($sourceId) : $sourceFile->file_size;
        $storageSizeAfter = $user->storage_size + (float) $sourceFileSize;

        if (!$destinationFile->isFolder()) {
            throw new \Error('Destination must be a folder');
        }

        if ($user->storage_limit <= $storageSizeAfter) {
            throw new \Error("You have reached the maximum storage limit for Lifebox.");
        }
        // Replicates file to the destination directory
        $newFile = $sourceFile->replicate(['parent_id', 'id']);
        $newFile->fill([
            'user_id' => $user->id,
            'parent_id' => $destinationFile->id,
            'file_status' => File::FILE_STATUS_CLOSE,
        ]);

        // Replicates the file on S3 bucket
        $fileS3Name = md5($sourceFile->file_name .'-copy'. '-' . $user->id . '-' . now() . '-' . Str::random());
        $s3Link = 'userstorage/' . $user->id . '/' . $fileS3Name;
        if ($sourceFile->isFile() && Storage::exists($sourceFile->file_reference)) {
            Storage::copy($sourceFile->file_reference, $s3Link);
            UserService::updateStorage($user, $sourceFileSize);
            $newFile->file_reference = $s3Link;
        }

        $newFile->save();
        $userFolder = FileService::getUserFolder($user->id);
        $userFolder->update([
            'file_size' => $user->storage_size,
        ]);

        // Replicate attached tag/s and corresponding properties too
        $attachedTags = $sourceFile->tags()->withPivot('id')->get();
        foreach ($attachedTags as $tag) {
            $newFileTag = FileTag::firstOrCreate([
                'file_id' => $newFile->id,
                'tag_id' => $tag->id
            ]);

            $attachedTagPropertyData = FileTagProperty::where('file_tag_id', $tag->pivot->id)->get();
            foreach ($attachedTagPropertyData as $datum) {
                FileTagProperty::firstOrCreate([
                    'file_tag_id' => $newFileTag->id,
                    'tag_property_id' => $datum->tag_property_id,
                    'value' => $datum->value,
                ]);
            }
        }

        // Recursive copying of files inside a source folder
        if ($sourceFile->isFolder()) {
            foreach ($sourceFile->files as $file) {
                self::copy($file->id, $newFile->id);
            }
        }
    }

    public static function open(File $file)
    {
        return tap($file)->update([
            'file_status' => File::FILE_STATUS_OPEN
        ]);
    }

    public static function close(File $file)
    {
        return tap($file)->update([
            'file_status' => File::FILE_STATUS_CLOSE
        ]);
    }

    public static function clearTrash($userId = null)
    {
        $user = User::find($userId);
        if (!$user) {
            return;
        }

        $userFolder = FileService::getUserFolder($user->id);
        $trashFolder = self::getTrashedFolder($userId);
        $newSize = $trashFolder->file_size * -1;

        self::removeTrashedAllFiles($trashFolder->id);
        UserService::updateStorage($user, $newSize);

        $trashFolder->update([
            'file_size' => (float) 0
        ]);
        $userFolder->update([
            'file_size' => $user->storage_size
        ]);
    }
}
