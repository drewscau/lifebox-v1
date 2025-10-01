<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Coupon
 *
 * @property int $id
 * @property string|null $stripe_id
 * @property int|null $amount_off
 * @property int|null $percent_off
 * @property int|null $max_redeem
 * @property string|null $last_redeem_date
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Coupon newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Coupon newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Coupon query()
 * @method static \Illuminate\Database\Eloquent\Builder|Coupon whereAmountOff($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Coupon whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Coupon whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Coupon whereLastRedeemDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Coupon whereMaxRedeem($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Coupon wherePercentOff($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Coupon whereStripeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Coupon whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Coupon extends Model
{
    use HasFactory;

    public const PRODUCT_TYPE_ANNUALLY = 'Annually';
    public const PRODUCT_TYPE_MONTHLY = 'Monthly';

    protected $table = 'coupons';

    protected $fillable = ['stripe_id', 'amount_off', 'percent_off', 'max_redeem', 'last_redeem_date', 'product_type'];
}
