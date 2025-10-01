<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Cashier\Subscription as CashierSubscription;
use Carbon\Carbon;

/**
 * App\Models\Subscription
 *
 * @property int $id
 * @property int $user_id
 * @property string|null $name
 * @property string|null $stripe_id
 * @property string|null $stripe_status
 * @property string|null $stripe_plan
 * @property int|null $quantity
 * @property \Illuminate\Support\Carbon|null $trial_ends_at
 * @property \Illuminate\Support\Carbon|null $ends_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $type
 * @property string|null $in_app_status
 * @property string|null $in_app_id
 * @property string|null $in_app_description
 * @property string|null $in_app_alias
 * @property string|null $in_app_title
 * @property string|null $in_app_type
 * @property int $in_app_valid
 * @property string|null $in_app_applicationUsername
 * @property string|null $in_app_expiryDate
 * @property string|null $in_app_purchaseDate
 * @property string|null $in_app_lastRenewalDate
 * @property string|null $in_app_renewalIntent
 * @property int $in_app_expired
 * @property int $in_app_trial_period
 * @property int $in_app_intro_period
 * @property int $in_app_billing_retry_period
 * @property-read \Illuminate\Database\Eloquent\Collection|\Laravel\Cashier\SubscriptionItem[] $items
 * @property-read int|null $items_count
 * @property-read \App\Models\User $owner
 * @property-read \App\Models\User $subscriber
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\SubscriptionTransaction[] $transactions
 * @property-read int|null $transactions_count
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|Subscription active()
 * @method static \Illuminate\Database\Eloquent\Builder|Subscription cancelled()
 * @method static \Illuminate\Database\Eloquent\Builder|Subscription ended()
 * @method static \Database\Factories\SubscriptionFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|Subscription incomplete()
 * @method static Builder|Subscription isInApp()
 * @method static Builder|Subscription isStripe()
 * @method static Builder|Subscription newModelQuery()
 * @method static Builder|Subscription newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Subscription notCancelled()
 * @method static \Illuminate\Database\Eloquent\Builder|Subscription notOnGracePeriod()
 * @method static \Illuminate\Database\Eloquent\Builder|Subscription notOnTrial()
 * @method static \Illuminate\Database\Eloquent\Builder|Subscription onGracePeriod()
 * @method static \Illuminate\Database\Eloquent\Builder|Subscription onTrial()
 * @method static \Illuminate\Database\Eloquent\Builder|Subscription pastDue()
 * @method static Builder|Subscription query()
 * @method static \Illuminate\Database\Eloquent\Builder|Subscription recurring()
 * @method static Builder|Subscription whereCreatedAt($value)
 * @method static Builder|Subscription whereEndsAt($value)
 * @method static Builder|Subscription whereId($value)
 * @method static Builder|Subscription whereInAppAlias($value)
 * @method static Builder|Subscription whereInAppApplicationUsername($value)
 * @method static Builder|Subscription whereInAppBillingRetryPeriod($value)
 * @method static Builder|Subscription whereInAppDescription($value)
 * @method static Builder|Subscription whereInAppExpired($value)
 * @method static Builder|Subscription whereInAppExpiryDate($value)
 * @method static Builder|Subscription whereInAppId($value)
 * @method static Builder|Subscription whereInAppIntroPeriod($value)
 * @method static Builder|Subscription whereInAppLastRenewalDate($value)
 * @method static Builder|Subscription whereInAppPurchaseDate($value)
 * @method static Builder|Subscription whereInAppRenewalIntent($value)
 * @method static Builder|Subscription whereInAppStatus($value)
 * @method static Builder|Subscription whereInAppTitle($value)
 * @method static Builder|Subscription whereInAppTrialPeriod($value)
 * @method static Builder|Subscription whereInAppType($value)
 * @method static Builder|Subscription whereInAppValid($value)
 * @method static Builder|Subscription whereName($value)
 * @method static Builder|Subscription whereQuantity($value)
 * @method static Builder|Subscription whereStripeId($value)
 * @method static Builder|Subscription whereStripePlan($value)
 * @method static Builder|Subscription whereStripeStatus($value)
 * @method static Builder|Subscription whereTrialEndsAt($value)
 * @method static Builder|Subscription whereType($value)
 * @method static Builder|Subscription whereUpdatedAt($value)
 * @method static Builder|Subscription whereUserId($value)
 * @mixin \Eloquent
 */
class Subscription extends CashierSubscription
{
    use HasFactory;

    const SUBSCRIPTION_STRIPE = 'stripe';
    const SUBSCRIPTION_IN_APP = 'in-app';

    const IN_APP_STATUS_REGISTERED = "REGISTERED";
    const IN_APP_STATUS_INVALID = "INVALID";
    const IN_APP_STATUS_VALID = "VALID";
    const IN_APP_STATUS_REQUESTED = "REQUESTED";
    const IN_APP_STATUS_INITIATED = "INITIATED";
    const IN_APP_STATUS_APPROVED = "APPROVED";
    const IN_APP_STATUS_FINISHED = "FINISHED";
    const IN_APP_STATUS_OWNED = "OWNED";
    const IN_APP_STATUS_DOWNLOADING = "DOWNLOADING";
    const IN_APP_STATUS_DOWNLOADED = "DOWNLOADED";


    const IN_APP_STATUS_RECOVERED = "SUBSCRIPTION_RECOVERED";
    const IN_APP_STATUS_RENEW = "SUBSCRIPTION_RENEWED";
    const IN_APP_STATUS_CANCELED = "SUBSCRIPTION_CANCELED";
    const IN_APP_STATUS_PURCHASED = "SUBSCRIPTION_PURCHASED";
    const IN_APP_STATUS_ON_HOLD = "SUBSCRIPTION_ON_HOLD";
    const IN_APP_STATUS_IN_GRACE_PERIOD = "SUBSCRIPTION_IN_GRACE_PERIOD";
    const IN_APP_STATUS_RESTARTED = "SUBSCRIPTION_RESTARTED";
    const IN_APP_STATUS_PRICE_CHANGE_CONFIRMED = "SUBSCRIPTION_PRICE_CHANGE_CONFIRMED";
    const IN_APP_STATUS_DEFERRED = "SUBSCRIPTION_DEFERRED";
    const IN_APP_STATUS_PAUSED = "SUBSCRIPTION_PAUSED";
    const IN_APP_STATUS_PAUSE_SCHEDULE_CHANGED = "SUBSCRIPTION_PAUSE_SCHEDULE_CHANGED";
    const IN_APP_STATUS_REVOKED = "SUBSCRIPTION_REVOKED";
    const IN_APP_STATUS_EXPIRED = "SUBSCRIPTION_EXPIRED";
    const IN_APP_STATUS_REFUNDED = "SUBSCRIPTION_REFUNDED";
    const IN_APP_STATUS_FAILED_TO_RENEW = "SUBSCRIPTION_FAILED_TO_RENEW";




    /**
     * Set the table name associated to this model
     */
    protected $table = 'subscriptions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'type',
        'name',
        'stripe_id',
        'stripe_status',
        'stripe_plan',
        'quantity',
        'trial_ends_at',
        'ends_at',
        'in_app_status',
        'in_app_id',
        'in_app_description',
        'in_app_alias',
        'in_app_title',
        'in_app_type',
        'in_app_valid',
        'in_app_applicationUsername',
        'in_app_expiryDate',
        'in_app_purchaseDate',
        'in_app_lastRenewalDate',
        'in_app_renewalIntent',
        'in_app_expired',
        'in_app_trial_period',
        'in_app_intro_period',
        'in_app_billing_retry_period'
    ];

    /**
     * Helper function for checking whether subscription is from in-app purchases
     *
     * @return boolean
     */
    public function fromInApp()
    {
        return $this->type == self::SUBSCRIPTION_IN_APP;
    }

    /**
     * Helper function for checking whether subscription is from Payment purchases
     *
     * @return boolean
     */
    public function fromStripe()
    {
        return $this->type == self::SUBSCRIPTION_STRIPE;
    }

    /**
     * Helper function for checking if In-App Purchase is valid
     *
     * @return boolean
     */
    public function isValidInApp()
    {
        switch ($this->in_app_status) {
            case Subscription::IN_APP_STATUS_CANCELED:
                // temporary check
                return ($this->in_app_expiryDate && !Carbon::parse($this->in_app_expiryDate)->isPast());
            case Subscription::IN_APP_STATUS_REVOKED:
            case Subscription::IN_APP_STATUS_ON_HOLD:
            case Subscription::IN_APP_STATUS_PAUSED:
                return false;

            case Subscription::IN_APP_STATUS_VALID:
            case Subscription::IN_APP_STATUS_APPROVED:
            case Subscription::IN_APP_STATUS_FINISHED:
            case Subscription::IN_APP_STATUS_OWNED:
            case Subscription::IN_APP_STATUS_RECOVERED:
            case Subscription::IN_APP_STATUS_RENEW:
            case Subscription::IN_APP_STATUS_IN_GRACE_PERIOD:
            case Subscription::IN_APP_STATUS_PAUSE_SCHEDULE_CHANGED:
                return true;

            default:
                return false;
        }
    }

    /**
     * Scope a query that only include Payment-based subscriptions
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeIsStripe(Builder $query): Builder
    {
        return $query->where('type', self::SUBSCRIPTION_STRIPE);
    }

    /**
     * Scope a query that only include in-app purchase-based subscriptions
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeIsInApp(Builder $query): Builder
    {
        return $query->where('type', self::SUBSCRIPTION_IN_APP);
    }

    /**
     * Get all subscription transactions (in-app purchases)
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(\App\Models\SubscriptionTransaction::class);
    }

    /**
     * Get the user that belongs to this subscription
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
