<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Tag;
use App\Models\Reminder;

/**
 * App\Models\File
 *
 * @property int $id
 * @property string $file_type
 * @property string|null $file_extension
 * @property int|null $user_id
 * @property string $file_name
 * @property string|null $file_reference
 * @property string $file_status
 * @property string $file_size
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $parent_id
 * @property-read \Illuminate\Database\Eloquent\Collection|File[] $children
 * @property-read int|null $children_count
 * @property-read \Illuminate\Database\Eloquent\Collection|File[] $files
 * @property-read int|null $files_count
 * @property-read File|null $folder
 * @property-read bool $parent_folder
 * @property-read \Illuminate\Database\Eloquent\Collection|File[] $parents
 * @property-read int|null $parents_count
 * @property-read \Illuminate\Database\Eloquent\Collection|Reminder[] $reminders
 * @property-read int|null $reminders_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FileTagProperty[] $tagProperties
 * @property-read int|null $tag_properties_count
 * @property-read \Illuminate\Database\Eloquent\Collection|Tag[] $tags
 * @property-read int|null $tags_count
 * @property-read \App\Models\User|null $user
 * @method static \Database\Factories\FileFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|File fileType()
 * @method static \Illuminate\Database\Eloquent\Builder|File folderType()
 * @method static \Illuminate\Database\Eloquent\Builder|File newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|File newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|File notTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|File query()
 * @method static \Illuminate\Database\Eloquent\Builder|File trashed()
 * @method static \Illuminate\Database\Eloquent\Builder|File trashedFolder($userId)
 * @method static \Illuminate\Database\Eloquent\Builder|File whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|File whereFileExtension($value)
 * @method static \Illuminate\Database\Eloquent\Builder|File whereFileName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|File whereFileReference($value)
 * @method static \Illuminate\Database\Eloquent\Builder|File whereFileSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder|File whereFileStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|File whereFileType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|File whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|File whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|File whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|File whereUserId($value)
 * @mixin \Eloquent
 */
class File extends Model
{
    use HasFactory;

    const FILE_STATUS_OPEN = 'open';
    const FILE_STATUS_CLOSE = 'close';
    const FILE_STATUS_ACTIVE = 'active';
    const FILE_STATUS_TRASHED = 'trashed';
    const FILE_TYPE_FOLDER = 'folder';
    const FILE_TYPE_FILE = 'file';

    const FILE_TRASHED = 'trashed';
    const FILE_INBOX = 'INBOX';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'parent_id',
        'user_id',
        'file_name',
        'file_status',
        'file_type',
        'file_extension',
        'file_size',
        'file_reference',
    ];

    protected $attributes = [
        'file_status' => self::FILE_STATUS_OPEN
    ];

    public function isTrashed()
    {
        return $this->attributes['file_status'] === self::FILE_STATUS_TRASHED;
    }


    public function isFile()
    {
        return$this->attributes['file_type'] === self::FILE_TYPE_FILE;
    }

    public function isFolder()
    {
        return $this->attributes['file_type'] === self::FILE_TYPE_FOLDER;
    }

    public function scopeTrashedFolder($q, $userId)
    {
        return  $q->where('file_name', self::FILE_TRASHED)
            ->where('user_id', $userId);
    }

    /**
     * Get user's parent folder instance in database
     *
     * @return bool
     */
    public function getParentFolderAttribute()
    {
        return $this->parents()->first();
    }

    public function scopeFolderType($q)
    {
        return $q->where('file_type', self::FILE_TYPE_FOLDER);
    }

    public function scopeFileType($q)
    {
        return $q->where('file_type', self::FILE_TYPE_FILE);
    }

    public function scopeNotTrashed($q)
    {
        return $q->where('file_status', '<>', self::FILE_STATUS_TRASHED);
    }

    public function scopeTrashed($q)
    {
        return $q->where('file_status', self::FILE_STATUS_TRASHED);
    }

    public function folder()
    {
        return $this->belongsTo(File::class, 'parent_id', 'id');
    }

    public function files()
    {
        return $this->hasMany(File::class, 'parent_id', 'id');
    }

    public function parents()
    {
        return $this->belongsToMany(File::class, 'file_file', 'file_id', 'parent_id');
    }

    public function children()
    {
        return $this->belongsToMany(File::class, 'file_file', 'parent_id', 'file_id');
    }

    public function tags()
    {
        return $this
            ->belongsToMany(Tag::class, 'file_tag', 'file_id', 'tag_id')
            ->using(FileTag::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reminders()
    {
        return $this->hasMany(Reminder::class);
    }

    public function tagProperties()
    {
        return $this->belongsToMany(FileTagProperty::class, 'file_tag', 'file_id', 'id');
    }
}
