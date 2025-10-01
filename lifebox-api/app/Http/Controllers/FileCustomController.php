<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\Reminder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Rules\FileOwnedRule;
use App\Services\FileService;
use App\Services\TagService;
use App\Services\UserService;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class FileCustomController extends Controller
{

    /**
     * Get all user file/folders
     *
     * Retrieve all recorded files and folders from the authenticated user
     *
     * @authenticated
     * @group Files
     * @return JsonResponse
     */
    public function getUserFiles()
    {
        $user = UserService::getCurrentUser();
        $userFolder = FileService::getUserFolder($user->id);
        return response()->json([
            'files' => $userFolder->files
        ]);
    }

    /**
     * Store a file
     *
     * @authenticated
     * @group Files
     * @bodyParam parent_id int required parent folder
     * @bodyParam file file required must be of type: jpeg,png,gif,jpg,doc,pdf,docx,txt
     * @bodyParam file_name string overwrite the uploaded file->file_name
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        $user = UserService::getCurrentUser();

        $data = $request->validate([
            'parent_id' => ['required', new FileOwnedRule],
            'file_name' => 'max:255',
            'file'  =>  'required|file|mimes:jpeg,png,gif,jpg,doc,pdf,docx,txt'
        ]);

        $file = $request->file('file');
        $fileName = $file->getClientOriginalName() . '.' . $file->getClientOriginalExtension();
        $fileS3Name = md5($fileName . '-' . $user->id . '-' . now() . '-' . Str::random());

        $storageSizeAfter = $user->storage_size + (float) FileService::getFileSize($file->getSize());
        if ($user->storage_limit <= $storageSizeAfter) {
            return response()->json([
                'code' => 'STORAGE_OVER_THE_LIMIT',
                'message' => "You have reached the maximum storage limit for Lifebox."
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $data['file_name'] = $data['file_name'] ?? $file->getClientOriginalName();
        $data['file_reference'] = $file->storeAs('userstorage/' . $user->id, $fileS3Name);
        $data['file_size'] = FileService::getFileSize($file->getSize());
        $data['user_id'] = $user->id;
        $data['file_type'] = File::FILE_TYPE_FILE;
        $data['file_extension'] = $file->getClientOriginalExtension();

        DB::beginTransaction();
        $folder = FileService::getFolderByParentId($data['parent_id']);

        try {
            $createdFile = File::create($data);
            $folder->update([
                'file_size' => $folder->file_size + $data['file_size']
            ]);
            UserService::updateStorage($user, $data['file_size']);
            $createdFile->load('folder');
            DB::commit();

            return response()->json([
                'message' => 'File uploaded',
                'File' => $createdFile,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'msg' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Retrieve all files of user
     *
     * Retrieve all recorded files and subfolders of a parent folder
     * Default is user's home folder (named after its ID)
     *
     * @authenticated
     * @group Files
     * @queryParam parent_id int folder->id OR file->id defaults to home folder?
     * @return JsonResponse
     */
    public function getContents(Request $request)
    {
        $parentId = $request->query('parent_id');

        if (!$parentId) {
            $currentFolder = FileService::getUserFolder();
            $parentId = $currentFolder->id;
        }
        $folder = FileService::getById($parentId);

        return response()->json([
            'files' => $folder->files,
            'current_folder' => $folder->id,
            'previous_folder' => $folder->folder_id
        ]);
    }

    /**
     * Create a folder
     *
     * Create a folder for current user
     *
     * @authenticated
     * @group Folders
     * @bodyParam file_name string required
     * @bodyParam parent_id int folder->id, defaults to users home folder?
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

        if (!isset($data['parent_id'])) {
            $data['parent_id'] = FileService::getUserFolder($user->id, 'id');
        }
        $parentFolder = FileService::getByFolderById($data['parent_id']);
        $folder = FileService::createFolder($user, $data['file_name'], $parentFolder);
        $folder->load('folder');
        return response()->json([
            'message' => 'Folder successfully created.',
            'folder' => $folder,
        ]);
    }

    /**
     * Get user home folder
     *
     * It says its deprecated. What does that mean? 🤷
     *
     * @authenticated
     * @group User
     * @deprecated
     * Use files?file_name=userid instead
     */
    public function home()
    {
        $user = UserService::getCurrentUser();
        $homePath = "userstorage/$user->id";
        $home = FileService::getUserFolder();

        return response()->json([
            'home_path' => $homePath,
            'home' => $home
        ]);
    }

    /**
     * Move file to trash
     *
     * It says this is deprecated. What does that mean? 🤷
     *
     * @authenticated
     * @group Files
     * @queryParam file_id int file_id
     * @deprecated
     */
    public function trash(Request $request)
    {
        $userId = Auth::id();
        $fileId = $request->query('file_id');

        $file = FileService::getById($fileId);

        if (!$file->isTrashed()) {
            FileService::trashed($file);
            $trash = FileService::getTrashedFolder($userId);
            return response()->json([
                'message' => 'Trashed',
                'Folder' =>  $trash->files,
            ]);
        }
        return response()->json([
            'code' => 'ALREADY_TRASHED',
            'message' => 'Already trashed',
        ]);
    }

    /**
     * Move a file
     *
     * Move file to a new destination
     *
     * TODO: Check if any client is using this endpoint, doesn't look like it works???
     *       I think move can be done thru FileController@update ???
     *
     * @authenticated
     * @group Files
     * @queryParam file_id int
     * @queryParam destination_id int
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function move(Request $request)
    {
        $fileId = $request->file_id;
        $destinationId = $request->destination_id;
        $file = FileService::getByFileById($fileId);
        $destination = FileService::getByFileById($destinationId);
        FileService::move($file, $destination);

        return response()->json([
            'message' => 'Moved',
            'Folder' =>  $destination->files,
        ]);
    }

    /**
     * Copy file
     *
     * Copy file to a new destination
     *
     * @authenticated
     * @group Files
     * @queryParam file_id int
     * @queryParam destination_id int
     * @param Request $request
     * @return JsonResponse
     */
    public function copyAndPaste(Request $request)
    {
        $fileId = $request->file_id;
        $destinationId = $request->destination_id;
        $user = UserService::getCurrentUser();

        DB::beginTransaction();
        $file = FileService::getById($fileId);

        try {
            FileService::copy($fileId, $destinationId);
            FileService::updateFolderSize($destinationId);

            $file = FileService::getById($destinationId);
            $file->load('files');

            UserService::updateStorage($user, $file->file_size);

            DB::commit();

            return response()->json([
                'message' => 'Copied And Pasted',
                'Folder' => $file->files
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'msg' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Open folder
     *
     * @authenticated
     * @group Folders
     * @queryParam folder_id int
     * @param Request $request
     * @return JsonResponse
     */
    public function openFolder(Request $request)
    {
        $folderId = $request->query('folder_id');
        $folder = FileService::open(
            FileService::getById($folderId)
        );

        return response()->json([
            'folder' =>  $folder,
            'parent' =>  $folder->folder,
        ]);
    }

    /**
     * Download file link
     *
     * Returns a download link. Change file_status to open
     *
     * @authenticated
     * @group Files
     * @queryParam file_id int file_id
     * @param Request $request
     * @return JsonResponse
     */
    public function downloadFile(Request $request)
    {
        $fileId = $request->query('file_id');
        $file = FileService::getByFileById($fileId);
        $file->update([
            'file_status' => File::FILE_STATUS_OPEN
        ]);

        return response()->json([
            'file' => $file,
            'url' => url('/api/files/' . $fileId . '/download'),
            'parent' => $file->folder
        ]);
    }

    /**
     * Tag a file
     *
     * Attach tag to a file
     *
     * @authenticated
     * @group Tags
     * @bodyParam tag_id int required tag
     * @bodyParam file_id int required file
     * @param Request $request
     * @return JsonResponse
     */
    public function tagFile(Request $request)
    {
        $data = $request->validate([
            'tag_id' => ['required', 'numeric', 'exists:tags,id'],
            'file_id' => ['required', 'numeric', 'exists:files,id'],
        ]);

        $file = FileService::getById($data['file_id']);
        if ($file->tags->contains($request->tag_id)) {
            return response()->json([
                'message' =>  'File already tagged with this tag.',
            ], 422);
        }

        $file->tags()->attach($request->tag_id);

        return response()->json([
            'message' => 'File tagged',
            'file' =>  $file,
            'tags' => $file->tags
        ], 201);
    }

    /**
     * Get file->tags
     *
     * @authenticated
     * @group Tags
     * @queryParam file_id int file
     * @param Request $request
     * @return JsonResponse
     */
    public function tagsOnFile(Request $request)
    {
        $id = $request->query('file_id');
        $file = FileService::getById($id);
        return response()->json([
            'tags' => $file->tags
        ]);
    }

    /**
     * Get file->reminders
     *
     * @authenticated
     * @group Reminders
     * @queryParam file_id int file
     * @param Request $request
     * @return JsonResponse
     */
    public function remindersOnFile(Request $request)
    {
        $id = $request->query('file_id');

        $file = FileService::getById($id);
        return response()->json([
            'reminders' => $file->reminders
        ]);
    }

    /**
     * Get size of file
     *
     * NOTE: I did not find a reference for this in routes/api
     *
     * @group Files
     * @urlParam id int file_id
     * @param $id
     * @return JsonResponse
     */
    private function size($id)
    {
        $userId = Auth::id();
        //$folderId = $request->folder_id;
        $folder = File::whereRaw("id = '$id' and file_type = 'folder' and user_id ='$userId' and file_status != 'trashed'")->get();
        if ($folder->children()->count() < 1) {
            $size = $folder->file_size;
            return response()->json([
                'size' => $size
            ]);
        }
        foreach ($folder->children as $child) {
            return $folder->file_size + $this->size($child->id);
        }
    }

    /**
     * Add file reminder
     *
     * @authenticated
     * @group Reminders
     * @bodyParam reminder_name string required
     * @bodyParam reminder_description string required
     * @bodyParam due_date_time string required date due
     * @param Request $request
     * @return JsonResponse
     */
    public function addFileReminder(Request $request)
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
        $reminder->load(['file', 'file.reminders']);

        return response()->json([
            'message' =>  'Reminder added',
            'file' =>  $reminder->file,
            'reminders' => $reminder->file->reminders
        ]);
    }

    /**
     * Show reminder notifications
     *
     * @authenticated
     * @group Reminders
     * @param Request $request
     * @return JsonResponse
     *
     */
    public function reminderNotifications(Request $request)
    {
        $userId = Auth::id();
        $time = Carbon::now('Australia/Brisbane');
        $reminders = Reminder::where('user_id', $userId)
            ->where('due_date_time', $time)
            ->limit(50)
            ->get();

        $notifications = [];
        foreach ($reminders as $reminder) {
            $diffDays = $time->diffInDays($reminder->due_date_time);
            $diffHours = $time->diffInHours($reminder->due_date_time);
            $diffMinutes = $time->diffInMinutes($reminder->due_date_time);

            $file = $reminder->file;
            if ($diffDays > 0 && $diffDays < 31) {
                array_push($notifications, "$reminder->reminder_name on $file->file_name is due in $diffDays day(s)");
            } elseif ($diffHours > 0) {
                array_push($notifications, "$reminder->reminder_name on $file->file_name is due in $diffHours hour(s)");
            } elseif ($diffMinutes > 0) {
                array_push($notifications, "$reminder->reminder_name on $file->file_name is due in $diffMinutes Minute(s)");
            }
        }
        if (count($notifications) > 0) {
            return response()->json([
                'notifications' => $notifications,
            ]);
        }
        return response()->json([
            'message' =>  'No files due',
        ]);
    }

    /**
     * Show 50 recent files
     *
     * @authenticated
     * @group Files
     * @param Request $request
     * @return JsonResponse
     */
    public function recent(Request $request)
    {
        $userId = Auth::id();
        $files = File::notTrashed()
            ->where([
                'file_status' => File::FILE_STATUS_OPEN,
                'user_id' => $userId
            ])
            ->orderBy('updated_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json([
            'files' => $files,
        ]);
    }

    /**
     * Create a tag
     *
     * @authenticated
     * @group Tags
     * @bodyParam tag_name string required
     * @bodyParam tag_description string
     * @bodyParam user_id int
     * @param Request $request
     * @return JsonResponse
     */
    public function createTag(Request $request)
    {
        $data  = $request->validate([
            'tag_name' => 'required|string|min:1',
            'tag_description' => 'string',
            'user_id' => 'exists:users,id',
            'tag_type_id' => 'nullable|integer',
            'is_outside_tag' => 'nullable|boolean',
        ]);

        $tag = TagService::create($data);
        return response()->json([
            'message' => 'Tag created',
            'tag' => $tag,
        ]);
    }
}
