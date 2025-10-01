<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * App\Models\FileTag
 *
 * @property int $id
 * @property int $tag_id
 * @property int $file_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\File $file
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FileTagProperty[] $properties
 * @property-read int|null $properties_count
 * @property-read \App\Models\Tag $tag
 * @method static \Database\Factories\FileTagFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|FileTag newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FileTag newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FileTag query()
 * @method static \Illuminate\Database\Eloquent\Builder|FileTag whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileTag whereFileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileTag whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileTag whereTagId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileTag whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class FileTag extends Pivot
{
    use HasFactory;

    protected $table = 'file_tag';

    protected $fillable  = [
        'file_id',
        'tag_id',
    ];

    public $incrementing = true; // cause this extends Pivot SO: https://stackoverflow.com/questions/41658090/laravel-model-id-null-after-save-id-is-incrementing/41658798

    public function properties()
    {
        return $this->hasMany(FileTagProperty::class);
    }

    public function tag()
    {
        return $this->belongsTo(Tag::class);
    }

    public function file()
    {
        return $this->belongsTo(File::class);
    }
}
