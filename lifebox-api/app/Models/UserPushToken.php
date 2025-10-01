<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


/**
 * App\Models\UserPushToken
 *
 * @property int $id
 * @property string|null $push_token
 * @property string|null $device_id
 * @property int $user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $device_platform
 * @property string|null $device_os
 * @property string|null $device_os_version
 * @property string|null $device_name
 * @property string|null $device_model
 * @property string|null $device_manufacturer
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|UserPushToken newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserPushToken newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserPushToken query()
 * @method static \Illuminate\Database\Eloquent\Builder|UserPushToken whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserPushToken whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserPushToken whereDeviceManufacturer($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserPushToken whereDeviceModel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserPushToken whereDeviceName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserPushToken whereDeviceOs($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserPushToken whereDeviceOsVersion($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserPushToken whereDevicePlatform($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserPushToken whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserPushToken wherePushToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserPushToken whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserPushToken whereUserId($value)
 * @mixin \Eloquent
 */
class UserPushToken extends Model
{
    use HasFactory;

    protected $table = 'user_push_token';

    protected $fillable = ['user_id', 'device_id', 'push_token', 'device_platform', 'device_os', 'device_os_version', 'device_name', 'device_model', 'device_manufacturer'];

    public function user()
    {
        return $this->belongsTo(User::class, 'id', 'user_id');
    }
}
