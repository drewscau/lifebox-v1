<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\TagProperty
 *
 * @property int $id
 * @property string $name
 * @property string $type
 * @property int $system_created
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $tag_id
 * @property-read \App\Models\Tag $tag
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FileTagProperty[] $values
 * @property-read int|null $values_count
 * @method static \Database\Factories\TagPropertyFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|TagProperty newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TagProperty newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TagProperty query()
 * @method static \Illuminate\Database\Eloquent\Builder|TagProperty whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TagProperty whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TagProperty whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TagProperty whereSystemCreated($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TagProperty whereTagId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TagProperty whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TagProperty whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class TagProperty extends Model
{
    use HasFactory;

    const TYPE_PHONE = 'phone';
    const TYPE_DATE = 'date';
    const TYPE_WEBSITE = 'website';
    const TYPE_OTHERS = 'others';
    const TYPE_PURCHASED = 'purchased';
    const TYPE_MOBILE = 'mobile';
    const TYPE_LINK = 'link';
    const ALLOWED_TYPES = [
        self::TYPE_DATE,
        self::TYPE_PHONE,
        self::TYPE_WEBSITE,
        self::TYPE_OTHERS,
        self::TYPE_PURCHASED,
        self::TYPE_MOBILE,
        self::TYPE_LINK,
    ];

    protected $fillable = [
        'tag_id',
        'name',
        'type',
        'system_created',
    ];

    public function tag()
    {
        return $this->belongsTo(Tag::class);
    }

    public function values()
    {
        return $this->hasMany(FileTagProperty::class, 'tag_property_id');
    }
}
