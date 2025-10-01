<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\FileTagProperty
 *
 * @property int $id
 * @property int $file_tag_id
 * @property int $tag_property_id
 * @property string $value
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\FileTag $fileTag
 * @property-read \App\Models\TagProperty $property
 * @method static \Database\Factories\FileTagPropertyFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|FileTagProperty newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FileTagProperty newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FileTagProperty query()
 * @method static \Illuminate\Database\Eloquent\Builder|FileTagProperty whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileTagProperty whereFileTagId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileTagProperty whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileTagProperty whereTagPropertyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileTagProperty whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileTagProperty whereValue($value)
 * @mixin \Eloquent
 */
class FileTagProperty extends Model
{
    use HasFactory;

    protected $fillable  = [
        'file_tag_id',
        'tag_property_id',
        'value',
    ];

    public function fileTag()
    {
        return $this->belongsTo(FileTag::class);
    }

    public function property()
    {
        return $this->belongsTo(TagProperty::class, 'tag_property_id', 'id');
    }
}
