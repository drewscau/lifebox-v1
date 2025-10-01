<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\File;
use Illuminate\Support\Facades\Auth;

class FileUserActivityObserver extends BaseUserActivityObserver
{
    const ACTIVITY_FILE_CREATED = 'Created a file. Filename: %s, File_ID: %s';
    const ACTIVITY_FILE_DELETED = 'Deleted a file. Filename: %s, File_ID: %s';
    const ACTIVITY_FILE_TRASHED = 'Trashed a file. Filename: %s, File_ID: %s';
    const ACTIVITY_FILE_RENAMED = 'Renamed a file. Original filename: %s, New filename: %s, File_ID: %s';
    const ACTIVITY_FOLDER_CREATED = 'Created a folder. Foldername: %s, Folder_ID: %s';
    const ACTIVITY_FOLDER_DELETED = 'Deleted a folder. Foldername: %s, Folder_ID: %s';
    const ACTIVITY_FOLDER_TRASHED = 'Trashed a folder. Foldername: %s, Folder_ID: %s';
    const ACTIVITY_FOLDER_RENAMED = 'Renamed a folder. Original foldername: %s, New foldername: %s, Folder_ID: %s';

    public function created(File $file): void
    {
        $this->createdFile($file) || $this->createdFolder($file);
    }

    public function deleted(File $file): void
    {
        $this->deletedFile($file) || $this->deletedFolder($file);
    }

    public function updated(File $file): void
    {
        $this->trashedFile($file) || $this->trashedFolder($file)
        || $this->renamedFile($file) || $this->renamedFolder($file);
    }

    private function createdFile(File $file): bool
    {
        if ($file->file_type === File::FILE_TYPE_FILE) {
            $this->createUserActivity(
                Auth::user(),
                sprintf(self::ACTIVITY_FILE_CREATED, $file->file_name, $file->id)
            );

            return true;
        }

        return false;
    }

    private function createdFolder(File $file): bool
    {
        if ($file->file_type === File::FILE_TYPE_FOLDER) {
            $this->createUserActivity(
                Auth::user(),
                sprintf(self::ACTIVITY_FOLDER_CREATED, $file->file_name, $file->id)
            );

            return true;
        }

        return false;
    }

    private function deletedFile(File $file): bool
    {
        if ($file->file_type === File::FILE_TYPE_FILE) {
            $this->createUserActivity(
                Auth::user(),
                sprintf(self::ACTIVITY_FILE_DELETED, $file->file_name, $file->id)
            );

            return true;
        }

        return false;
    }

    private function deletedFolder(File $file): bool
    {
        if ($file->file_type === File::FILE_TYPE_FOLDER) {
            $this->createUserActivity(
                Auth::user(),
                sprintf(self::ACTIVITY_FOLDER_DELETED, $file->file_name, $file->id)
            );

            return true;
        }

        return false;
    }

    private function trashedFile(File $file): bool
    {
        if (
            $file->file_type === File::FILE_TYPE_FILE
            && $file->file_status === File::FILE_STATUS_TRASHED
            && $file->file_status !== $file->getOriginal('file_status')
        ) {
            $this->createUserActivity(
                Auth::user(),
                sprintf(self::ACTIVITY_FILE_TRASHED, $file->file_name, $file->id)
            );

            return true;
        }

        return false;
    }

    private function trashedFolder(File $file): bool
    {
        if (
            $file->file_type === File::FILE_TYPE_FOLDER
            && $file->file_status === File::FILE_STATUS_TRASHED
            && $file->file_status !== $file->getOriginal('file_status')
        ) {
            $this->createUserActivity(
                Auth::user(),
                sprintf(self::ACTIVITY_FOLDER_TRASHED, $file->file_name, $file->id)
            );

            return true;
        }

        return false;
    }

    private function renamedFile(File $file): bool
    {
        if ($file->file_type === File::FILE_TYPE_FILE
            && $file->getOriginal('file_name') !== $file->file_name
        ) {
            $this->createUserActivity(
                Auth::user(),
                sprintf(
                    self::ACTIVITY_FILE_RENAMED,
                    $file->getOriginal('file_name'),
                    $file->file_name,
                    $file->id
                )
            );

            return true;
        }

        return false;
    }

    private function renamedFolder(File $file): bool
    {
        if (
            $file->file_type === File::FILE_TYPE_FOLDER
            && $file->getOriginal('file_name') !== $file->file_name
        ) {
            $this->createUserActivity(
                Auth::user(),
                sprintf(
                    self::ACTIVITY_FOLDER_RENAMED,
                    $file->getOriginal('file_name'),
                    $file->file_name,
                    $file->id
                )
            );

            return true;
        }

        return false;
    }
}
