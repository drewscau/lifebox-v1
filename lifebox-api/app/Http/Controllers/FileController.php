<?php

namespace App\Http\Controllers;

use App\Exceptions\UserStorageLimitException;
use App\Services\FileHelperService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\User;
use App\Models\OauthAccessToken;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Rules\FileOwnedRule;
use App\Services\FileService;
use App\Services\UserService;
use GuzzleHttp\Psr7\MimeType;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Passport\Passport;

class FileController extends Controller
{
    /**
     * List files
     *
     * Display a listing of the resource.
     *
     * @authenticated
     * @group Files
     * @queryParam limit int defaults to 50
     * @queryParam sort_by string defaults to 'id'
     * @queryParam sort_dir string defaults to 'asc'
     * @queryParam search_text string filter by search_text
     * @queryParam parent_id int filter by parent directory
     * @queryParam type string 'file' or 'folder' defaults to both
     * @queryParam trash bool filter files that are trashed or not_trashed defaults to not_trashed
     * @queryParam status bool filter by status one of: open,closed,active,trashed
     * @queryParam show_trash_folder bool toggle showing of trashed folders, defaults to false
     * @queryParam file_name string
     * @queryParam show_content bool
     *
     * @return Response
     */
    public function index(Request $request)
    {
        $limit = $request->query('limit', 50);
        $sortBy = $request->query('sort_by', 'id');
        $sortDir = $request->query('sort_dir', 'asc');
        $searchText = $request->query('search_text');
        $parentId = $request->query('parent_id');
        $type = $request->query('type');
        $trash = $request->query('trash');
        $userId = $request->query('user_id');
        $tags = $request->query('tags');
        $status = $request->query('status');
        $showTrashFolder = $request->query('show_trash_folder');
        $fileName = $request->query('file_name');
        $showContent = $request->query('show_content');

        $rs = FileService::search(
            $userId,
            $searchText,
            $fileName,
            $showContent,
            $showTrashFolder,
            $tags,
            $status,
            $type,
            $trash,
            $parentId,
            $sortBy,
            $sortDir,
            $limit
        );

        return response()->json($rs);
    }

    /**
     * Create a file
     *
     * @authenticated
     * @group Files
     * @bodyParam parent_id int required parent directory
     * @bodyParam file_name string
     * @bodyParam file_reference string
     * @bodyParam file_size number
     * @bodyParam file_extension string
     * @bodyParam user_id int
     * @param Request $request
     * @return JsonResponse
     * @throws UserStorageLimitException
     */
    public function createFileEntry(Request $request)
    {
        $data = $request->validate([
            'parent_id' => ['required', new FileOwnedRule],
            'file_name' => 'max:255',
            'file_reference' => 'string',
            'file_size' => 'numeric',
            'file_extension' => 'string',
            'user_id' => 'exists:users,id'
        ]);

        $data['file_type'] = File::FILE_TYPE_FILE;
        $data['file_size'] = FileService::getFileSize($data['file_size']);

        $folder = FileService::getFolderByParentId($data['parent_id']);
        $user = User::findOrFail($data['user_id']);

        if (FileHelperService::isMoreThanUserStorage($user, $data['file_size'])) {
            throw new UserStorageLimitException();
        }

        DB::beginTransaction();

        try {
            $createdFile = File::create($data);

            $folder->update([
                'file_size' => $folder->file_size + $data['file_size']
            ]);

            UserService::updateStorage($createdFile->user, $data['file_size']);

            DB::commit();
            return $createdFile->load('folder');
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'msg' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Store a file
     *
     * @authenticated
     * @group Files
     * @bodyParam parent_id int required parent directory
     * @bodyParam file_name string
     * @bodyParam file string required file to be uploaded
     * @param Request $request
     * @return JsonResponse
     * @throws UserStorageLimitException
     */
    public function store(Request $request)
    {
        $user = UserService::getCurrentUser();

        $data = $request->validate([
            'parent_id' => ['required', new FileOwnedRule],
            'file_name' => 'max:255',
            'file'  =>  'required|file|mimes:jpeg,x-png,png,gif,jpg,doc,pdf,docx,txt,xls,xlsx|max:'. (1* 1024 * 1024)
        ]);

        $file = $request->file('file');
        $fileName = $file->getClientOriginalName() . '.' . $file->getClientOriginalExtension();
        $fileS3Name = md5($fileName . '-' . $user->id . '-' . now() . '-' . Str::random());

        if (FileHelperService::isMoreThanUserStorage(
                $user,
                FileService::getFileSize($file->getSize())
            )
        ) {
            throw new UserStorageLimitException();
        }

        $data['file_name'] = $data['file_name'] ?? $file->getClientOriginalName();
        $data['file_reference'] = $file->storeAs('userstorage/' . $user->id, $fileS3Name);
        $data['file_size'] = FileService::getFileSize($file->getSize());
        $data['user_id'] = $user->id;
        $data['file_type'] = File::FILE_TYPE_FILE;
        $data['file_status'] = File::FILE_STATUS_OPEN;
        $data['file_extension'] = $file->getClientOriginalExtension();

        $folder = FileService::getFolderByParentId($data['parent_id']);
        DB::beginTransaction();
        try {
            $createdFile = File::create($data);
            $folder->update([
                'file_size' => $folder->file_size + $data['file_size']
            ]);
            UserService::updateStorage($user, $data['file_size']);
            $createdFile->load('folder');
            DB::commit();

            return response()->json($createdFile);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'msg' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Restore a file
     *
     * Set file status to 'close'
     *
     * @authenticated
     * @group Files
     * @urlParam file_id int required
     * @param $fileId
     * @return JsonResponse
     */
    public function restore($fileId)
    {
        $user = UserService::getCurrentUser();
        $userFolder = FileService::getUserFolder($user->id);
        $file = File::trashed()->findOrFail($fileId);
        $file->update([
            'file_status' => File::FILE_STATUS_CLOSE,
            'parent_id' => $userFolder->id
        ]);

        if ($file->isFolder()) {
            FileService::markStatusAllFiles($fileId, File::FILE_STATUS_CLOSE);
        }

        return response()->json($file, Response::HTTP_OK);
    }

    /**
     * Update a file
     *
     * @authenticated
     * @group Files
     * @bodyParam parent_id int required parent directory
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function update(Request $request, $id)
    {
        $file = FileService::getById($id);
        if (
            $file->file_name == UserService::id() ||
            $file->file_name == File::FILE_TRASHED
        ) {
            return response()->json([
                'code' => 'UPDATE_FORBIDDEN_FOR_BASEFOLDER'
            ], Response::HTTP_FORBIDDEN);
        }
        $data = $request->validate([
            'parent_id' => [new FileOwnedRule],
            'file_name' => ['max:255'],
            'file_status' => [Rule::in([
                File::FILE_STATUS_CLOSE,
                File::FILE_STATUS_OPEN
            ])]
        ]);
        if (isset($data['parent_id'])) {
            $destination = FileService::getById($data['parent_id']);
            FileService::move($file, $destination);
        }
        $file = tap($file)->update($data);

        return response()->json($file);
    }

    /**
     * Clear Trashed files
     *
     * @authenticated
     * @group Files
     * Remove trashed files of logged-in user and recalculate filesize
     * @return JsonResponse
     */
    public function clearTrash()
    {
        $userId = UserService::id();
        FileService::clearTrash($userId);
        return response()->json([], Response::HTTP_NO_CONTENT);
    }

    /**
     * Get a file
     *
     * @authenticated
     * @group Files
     * @queryParam trash bool defaults to false
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function show(Request $request, $id)
    {
        $trash = $request->query('trash');
        $file = FileService::getById($id, $trash, [], ['files']);
        $file->load(['tags']);
        return response()->json($file);
    }

    /**
     * Create a folder
     *
     * @authenticated
     * @group Folders
     * @bodyParam parent_id folder->id defaults to user->id
     * @bodyParam file_name string required folder_name
     * @param Request $request
     * @return JsonResponse
     */
    public function createFolder(Request $request)
    {
        $user = UserService::getCurrentUser();
        $data = $request->validate([
            'parent_id' => [new FileOwnedRule],
            'file_name' => 'required|max:255'
        ]);

        $data['parent_id'] = $data['parent_id'] ?? $user->id;
        $parentFolder = FileService::getByFolderById($data['parent_id']);
        $folder = FileService::createFolder($user, $data['file_name'], $parentFolder);
        $folder->load('folder');

        return response()->json($folder, Response::HTTP_CREATED);
    }

    /**
     * Download a file
     *
     * @authenticated
     * @group Files
     * @queryParam preview bool if not set set as attachment?
     * @param Request $request
     * @param $id
     * @return JsonResponse|Response|mixed|void
     */
    public function download(Request $request, $id)
    {
        $preview = $request->query('preview');

        /** @var File */
        $file = File::findOrFail($id);

        if (
            !auth()->user() &&
            $request->has('user_id') &&
            $file->user_id != $request->query('user_id')
        ) {
            abort(Response::HTTP_UNAUTHORIZED);
        }

        $file->update([
            'file_status' => File::FILE_STATUS_OPEN
        ]);

        if ($file->isFolder()) {
            return FileService::downloadFolder($file->id);
        }

        $mimeType = new MimeType();
        $mime = $mimeType->fromExtension($file->file_extension);

        try {
            $size = Storage::getDriver()->getSize($file->file_reference);

            $response =  [
                'Content-Type' => $mime,
                'Content-Length' => $size ?? FileService::getFileSizeOriginal($file->file_size),
            ];

            if (!$preview) {
                $response['Content-Transfer-Encoding'] = 'binary';
                $response['Content-Description'] = 'File Transfer';
                $response['Content-Disposition'] = "attachment; filename={$file->file_name}";
            }

            return response()->make(Storage::get($file->file_reference), 200, $response);
        } catch (Exception $e) {
            return response()->json([
                'msg' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Trash a file
     *
     * Set file status as trashed
     *
     * @authenticated
     * @group Files
     * @urlParam id int required file_id
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function trash(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $file = FileService::getById($id);
            FileService::trashed($file);
            DB::commit();

            return response()->json($file);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'msg' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Delete a file
     *
     * @authenticated
     * @group Files
     * @urlParam id int required file_id
     * @param $id
     * @return JsonResponse
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $file = FileService::getById($id);
            $newSize = $file->file_size * -1;
            Storage::delete($file->file_reference);
            UserService::updateStorage($file->user, $newSize);
            FileService::removeById($id);
            DB::commit();
            return response()->json([], Response::HTTP_NO_CONTENT);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'msg' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Copy a file
     *
     * @authenticated
     * @group Files
     * @urlParam id int required file_id
     * @bodyParam destination_id int folder_id? where file will be copied to
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function copy(Request $request, $id)
    {
        $user = UserService::getCurrentUser();

        $data = $request->validate([
            'destination_id' => [new FileOwnedRule]
        ]);

        DB::beginTransaction();
        $file = FileService::getById($id);

        try {
            FileService::copy($id, $data['destination_id']);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'msg' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        FileService::updateFolderSize($data['destination_id']);

        $file = FileService::getById($data['destination_id']);
        $file->load('files');

        return response()->json($file);
    }
}
