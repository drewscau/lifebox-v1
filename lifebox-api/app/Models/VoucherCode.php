<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\VoucherCode
 *
 * @property int $id
 * @property int $coupon_id
 * @property int $retailer_id
 * @property string $code
 * @property int|null $max_redeem
 * @property string|null $last_redeem_date
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Coupon $coupon
 * @method static \Illuminate\Database\Eloquent\Builder|VoucherCode newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|VoucherCode newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|VoucherCode query()
 * @method static \Illuminate\Database\Eloquent\Builder|VoucherCode whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VoucherCode whereCouponId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VoucherCode whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VoucherCode whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VoucherCode whereLastRedeemDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VoucherCode whereMaxRedeem($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VoucherCode whereRetailerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VoucherCode whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class VoucherCode extends Model
{
    use HasFactory;

    protected $table = 'voucher_codes';

    protected $fillable = ['code', 'coupon_id', 'retailer_id', 'max_redeem', 'last_redeem_date'];

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function retailer()
    {
        return $this->belongsTo(Retailer::class);
    }
}
