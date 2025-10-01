<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Retailer
 *
 * @property int $id
 * @property string $company
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Retailer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Retailer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Retailer query()
 * @method static \Illuminate\Database\Eloquent\Builder|Retailer whereCompany($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Retailer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Retailer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Retailer whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Retailer whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Retailer extends Model
{
    use HasFactory;

    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const DEFAULT_RETAILER_COMPANY = 'Lifebox';

    protected $table = 'retailers';

    protected $fillable = ['company', 'status'];
}
