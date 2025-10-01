<?php

namespace App\Models;

use Illuminate\Support\Facades\Hash;
use Mail;
use App\Mail\VerificationMail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Laravel\Cashier\Billable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * App\Models\User
 *
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property string|null $mobile
 * @property string $email
 * @property string $account_number
 * @property string $username
 * @property string|null $lifebox_email
 * @property string $user_type
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $stripe_id
 * @property string|null $card_brand
 * @property string|null $card_last_four
 * @property string|null $trial_ends_at
 * @property string $storage_size
 * @property string $storage_limit
 * @property string|null $profile_picture
 * @property string $user_status
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\OauthAccessToken[] $OauthAcessToken
 * @property-read int|null $oauth_acess_token_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\Laravel\Passport\Client[] $clients
 * @property-read int|null $clients_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\File[] $files
 * @property-read int|null $files_count
 * @property-read bool $activated
 * @property-read bool $subscribed
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection|\Illuminate\Notifications\DatabaseNotification[] $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\UserPushToken[] $pushtokens
 * @property-read int|null $pushtokens_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Reminder[] $reminders
 * @property-read int|null $reminders_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Subscription[] $subscriptions
 * @property-read int|null $subscriptions_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Tag[] $tags
 * @property-read int|null $tags_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\Laravel\Passport\Token[] $tokens
 * @property-read int|null $tokens_count
 * @method static Builder|User active()
 * @method static \Database\Factories\UserFactory factory(...$parameters)
 * @method static Builder|User newModelQuery()
 * @method static Builder|User newQuery()
 * @method static Builder|User notTerminated()
 * @method static Builder|User query()
 * @method static Builder|User sortBy(int $column, string $direction)
 * @method static Builder|User subscribed()
 * @method static Builder|User whereAccountNumber($value)
 * @method static Builder|User whereCardBrand($value)
 * @method static Builder|User whereCardLastFour($value)
 * @method static Builder|User whereCreatedAt($value)
 * @method static Builder|User whereEmail($value)
 * @method static Builder|User whereEmailVerifiedAt($value)
 * @method static Builder|User whereFirstName($value)
 * @method static Builder|User whereId($value)
 * @method static Builder|User whereLastName($value)
 * @method static Builder|User whereLifeboxEmail($value)
 * @method static Builder|User whereMobile($value)
 * @method static Builder|User wherePassword($value)
 * @method static Builder|User whereProfilePicture($value)
 * @method static Builder|User whereRememberToken($value)
 * @method static Builder|User whereStorageLimit($value)
 * @method static Builder|User whereStorageSize($value)
 * @method static Builder|User whereStripeId($value)
 * @method static Builder|User whereTrialEndsAt($value)
 * @method static Builder|User whereUpdatedAt($value)
 * @method static Builder|User whereUserStatus($value)
 * @method static Builder|User whereUserType($value)
 * @method static Builder|User whereUsername($value)
 * @method static Builder|User withoutAdmin()
 * @mixin \Eloquent
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, Billable;

    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_SUBSCRIBED = 'subscribed';
    const STATUS_UNSUBSCRIBED = 'unsubscribed';

    const USER_TYPE_ADMIN = 'administrator';
    const USER_TYPE_USER = 'user';
    const USER_TYPE_GENERAL = 'general';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'mobile',
        'account_number',
        'username',
        'user_type',
        'user_status',
        'email',
        'lifebox_email',
        'password',
        'storage_size',
        'storage_limit',
        'profile_picture',
    ];

    protected $attributes = [
        'user_type' => self::USER_TYPE_USER
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function sendEmailVerificationNotification()
    {
        Mail::to($this->email)->send(new VerificationMail($this));
    }

    /**
     * Scope users that had active status
     *
     * @return Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($q)
    {
        return $this->where('user_status', self::STATUS_ACTIVE);
    }

    /**
     * Scope users that are not terminated in status
     *
     * @return Illuminate\Database\Eloquent\Builder
     */
    public function scopeNotTerminated($q)
    {
        return $this->where('user_status', '!=', self::STATUS_INACTIVE);
    }

    /**
     * Scope users subscription status
     *
     * @return Illuminate\Database\Eloquent\Builder
     */
    public function scopeSubscribed($query)
    {
        $query->whereHas('user', function ($q) {
            $q->subscribed();
        });
    }


    /**
     * Scope all users taht are not admins
     *
     * @return Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithoutAdmin($q)
    {
        return $this->where('user_type', "!=",  self::USER_TYPE_ADMIN);
    }

    /**
     * Get user's subscription status
     *
     * @return bool
     */
    public function getSubscribedAttribute()
    {
        return $this->user_status === self::STATUS_SUBSCRIBED;
    }

    /**
     * Get user's activation status
     *
     * @return bool
     */
    public function getActivatedAttribute()
    {
        return $this->user_status !== self::STATUS_INACTIVE;
    }

    /**
     * Generate user token via Passport
     *
     * @return string
     */
    public function generateToken(array $scopes = ['lifebox'])
    {
        $token =  $this->createToken(
            $this->email . '-' . $this->password . '-' . now(),
            $scopes
        );
        $token->token->expires_at = now()->addYear(1);
        $token->token->save();

        return $token->accessToken;
    }

    /**
     * Check if user role is an admin
     *
     * @return Boolean
     */
    public function isAdmin()
    {
        return $this->user_type === self::USER_TYPE_ADMIN;
    }

    /**
     * Get OAuth model
     */
    function OauthAcessToken()
    {
        return $this->hasMany('App\Models\OauthAccessToken');
    }

    /**
     * Scope a query that sorts users by a specific column
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
                return $query->orderBy('first_name', $direction);
                break;
            case 1:
                return $query->orderBy('username', $direction);
                break;
            case 2:
                return $query->orderBy('mobile', $direction);
                break;
            case 3:
                return $query->orderBy('email', $direction);
                break;
            case 4:
                return $query->orderBy('lifebox_email', $direction);
                break;
            case 5:
                return $query->orderBy('account_number', $direction);
                break;
            case 6:
                return $query->orderBy('user_type', $direction);
                break;
            case 7:
                return $query->orderBy('user_status', $direction);
                break;
            case 8:
                return $query->orderBy('created_at', $direction);
                break;
            default:
                return $query->orderBy('id', 'asc');
                break;
        }
    }

    /**
     * Get user files
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function files(): HasMany
    {
        return $this->hasMany(File::class);
    }

    /**
     * Get user tags
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class);
    }

    /**
     * Get user reminders
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function reminders(): HasMany
    {
        return $this->hasMany(Reminder::class);
    }

    /**
     * Override the subscriptions() from Larave\Cashier\Billable
     * to inject our own subscriptions
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function subscriptions()
    {
        return $this->hasMany(\App\Models\Subscription::class, $this->getForeignKey())->orderBy('created_at', 'desc');
    }

    public function findForPassport($username)
    {
        return $this->where('lifebox_email', $username)->first();
    }

    public function validateForPassportPasswordGrant($password)
    {
        return Hash::check($password, $this->password);
    }

    /**
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function pushtokens()
    {
        return $this->hasMany(UserPushToken::class, $this->getForeignKey());
    }
}
