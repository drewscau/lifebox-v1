<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Tag
 *
 * @property int $id
 * @property string $tag_name
 * @property string|null $tag_description
 * @property int|null $user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $system_created
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\File[] $files
 * @property-read int|null $files_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\TagProperty[] $properties
 * @property-read int|null $properties_count
 * @property-read \App\Models\User|null $user
 * @method static \Database\Factories\TagFactory factory(...$parameters)
 * @method static Builder|Tag newModelQuery()
 * @method static Builder|Tag newQuery()
 * @method static Builder|Tag query()
 * @method static Builder|Tag systemGenerated()
 * @method static Builder|Tag whereCreatedAt($value)
 * @method static Builder|Tag whereId($value)
 * @method static Builder|Tag whereSystemCreated($value)
 * @method static Builder|Tag whereTagDescription($value)
 * @method static Builder|Tag whereTagName($value)
 * @method static Builder|Tag whereUpdatedAt($value)
 * @method static Builder|Tag whereUserId($value)
 * @mixin \Eloquent
 */
class Tag extends Model
{
    use HasFactory;

    public const DEFAULTS = [
        'BILLS'                     => ['Supplier', 'What', 'Date', 'Account Number', 'Website', 'Phone'],
        'LEGAL & TAXES'             => ['Prepared By', 'What', 'Date', 'Further Info', 'Company Website', 'Phone'],
        'PURCHASES & WARRANTIES'    => ['Retailer', 'Purchased', 'Date', 'Warrant Period', 'Product Website'],
        'IDENTIFICATION'            => ['Type', 'What', 'Expiry', 'Photo', 'Website', 'Phone'],
        'HEALTH & INSURANCES'       => ['Supplier', 'Coverage', 'Expiry', 'Policy Number', 'Website', 'Phone'],
        'BANKING & FINANCE'         => ['Bank', 'Name', 'Date Opened', 'Account Number', 'Photo', 'Website', 'Branch'],
        'RECEIPTS'                  => ['Retailer', 'What', 'Date Purchased', 'Value', 'Photo'],
        'OTHER INVESTMENTS'         => ['Provider', 'What', 'Start Date', 'Account/Reference', 'Website', 'Value'],
        'STATEMENTS'                => ['Institution', 'Type of Statement', 'Start Date'],
        'WORK'                      => ['What', 'Date', 'Further Info'],
        'OTHER'                     => [],
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'tag_name',
        'tag_description',
        'user_id',
        'tag_type_id',
        'is_outside_tag',
        'system_created',
    ];

    /**
     * Scope a query that only include system-generated tags
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSystemGenerated(Builder $query) : Builder
    {
        return $query->where('system_created', 1);
    }

    public function files()
    {
        return $this->belongsToMany(File::class, 'file_tag', 'tag_id', 'file_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function properties()
    {
        return $this->hasMany(TagProperty::class, 'tag_id');
    }
}
