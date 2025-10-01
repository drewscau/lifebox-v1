<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * App\Models\RetailerUser
 *
 * @property int $id
 * @property string $retailer_account_number
 * @property string $retailer_password
 * @property string $retailer_status
 * @property string $company
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static Builder|RetailerUser newModelQuery()
 * @method static Builder|RetailerUser newQuery()
 * @method static Builder|RetailerUser query()
 * @method static Builder|RetailerUser sortBy(int $column, string $direction)
 * @method static Builder|RetailerUser whereCompany($value)
 * @method static Builder|RetailerUser whereCreatedAt($value)
 * @method static Builder|RetailerUser whereId($value)
 * @method static Builder|RetailerUser whereRetailerAccountNumber($value)
 * @method static Builder|RetailerUser whereRetailerPassword($value)
 * @method static Builder|RetailerUser whereRetailerStatus($value)
 * @method static Builder|RetailerUser whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class RetailerUser extends Model
{
    use HasFactory;

    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'retailer_account_number',
        'retailer_status',
        'retailer_password',
        'company',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'retailer_password',
    ];

    protected $attributes = [
        'retailer_status' => self::STATUS_ACTIVE
    ];

    /**
     * Scope a query that sorts retailer by a specific column
     *
     * @param  Illuminate\Database\Eloquent\Builder $query
     * @param int $column
     * @param string $direction
     * 
     * @return Illuminate\Database\Eloquent\Builder
     */
    public function scopeSortBy(Builder $query, int $column, string $direction): Builder
    {
        switch ($column) {
            case 0:
                return $query->orderBy('retailer_account_number', $direction);
                break;
            case 1:
                return $query->orderBy('company', $direction);
                break;
            case 2:
                return $query->orderBy('retailer_status', $direction);
                break;
            default:
                return $query->orderBy('id', 'asc');
                break;
        }
    }
}
