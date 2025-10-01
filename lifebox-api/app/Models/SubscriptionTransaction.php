<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\SubscriptionTransaction
 *
 * @property int $id
 * @property int $subscription_id
 * @property string|null $type
 * @property string|null $transaction_id
 * @property string|null $original_transaction_id
 * @property string|null $purchase_token
 * @property string|null $signature
 * @property string|null $receipt
 * @property string|null $developerPayload
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $in_app_ownership_type
 * @property string|null $is_in_intro_offer_period
 * @property string|null $is_trial_period
 * @property string|null $subscription_group_identifier
 * @property string|null $expires_date
 * @property string|null $original_purchase_date
 * @property string|null $purchase_date
 * @property string|null $link_purchase_token
 * @property-read \App\Models\Subscription $subscription
 * @method static Builder|SubscriptionTransaction newModelQuery()
 * @method static Builder|SubscriptionTransaction newQuery()
 * @method static Builder|SubscriptionTransaction query()
 * @method static Builder|SubscriptionTransaction sameAs(string $id, string $storeType)
 * @method static Builder|SubscriptionTransaction whereCreatedAt($value)
 * @method static Builder|SubscriptionTransaction whereDeveloperPayload($value)
 * @method static Builder|SubscriptionTransaction whereExpiresDate($value)
 * @method static Builder|SubscriptionTransaction whereId($value)
 * @method static Builder|SubscriptionTransaction whereInAppOwnershipType($value)
 * @method static Builder|SubscriptionTransaction whereIsInIntroOfferPeriod($value)
 * @method static Builder|SubscriptionTransaction whereIsTrialPeriod($value)
 * @method static Builder|SubscriptionTransaction whereLinkPurchaseToken($value)
 * @method static Builder|SubscriptionTransaction whereOriginalPurchaseDate($value)
 * @method static Builder|SubscriptionTransaction whereOriginalTransactionId($value)
 * @method static Builder|SubscriptionTransaction wherePurchaseDate($value)
 * @method static Builder|SubscriptionTransaction wherePurchaseToken($value)
 * @method static Builder|SubscriptionTransaction whereReceipt($value)
 * @method static Builder|SubscriptionTransaction whereSignature($value)
 * @method static Builder|SubscriptionTransaction whereSubscriptionGroupIdentifier($value)
 * @method static Builder|SubscriptionTransaction whereSubscriptionId($value)
 * @method static Builder|SubscriptionTransaction whereTransactionId($value)
 * @method static Builder|SubscriptionTransaction whereType($value)
 * @method static Builder|SubscriptionTransaction whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class SubscriptionTransaction extends Model
{
    use HasFactory;

    const TRANSACTION_ANDROID = 'android-playstore';
    const TRANSACTION_IOS = 'ios-appstore';

    const PROVIDER_ANDROID = 'google_play';
    const PROVIDER_IOS = 'app_store';

    const ERROR_CODES_BASE = 6777000;

    const VALIDATION_INVALID_PAYLOAD   = 6778001;
    const VALIDATION_CONNECTION_FAILED = 6778002;
    const VALIDATION_PURCHASE_EXPIRED  = 6778003;
    const VALIDATION_PURCHASE_CONSUMED = 6778004;
    const VALIDATION_INTERNAL_ERROR    = 6778005;
    const VALIDATION_NEED_MORE_DATA    = 6778006;

    const ERR_SETUP               = self::ERROR_CODES_BASE + 1; //
    const ERR_LOAD                = self::ERROR_CODES_BASE + 2; //
    const ERR_PURCHASE            = self::ERROR_CODES_BASE + 3; //
    const ERR_LOAD_RECEIPTS       = self::ERROR_CODES_BASE + 4;
    const ERR_CLIENT_INVALID      = self::ERROR_CODES_BASE + 5;
    const ERR_PAYMENT_CANCELLED   = self::ERROR_CODES_BASE + 6; // Purchase has been cancelled by user.
    const ERR_PAYMENT_INVALID     = self::ERROR_CODES_BASE + 7; // Something suspicious about a purchase.
    const ERR_PAYMENT_NOT_ALLOWED = self::ERROR_CODES_BASE + 8;
    const ERR_UNKNOWN             = self::ERROR_CODES_BASE + 10; //
    const ERR_REFRESH_RECEIPTS    = self::ERROR_CODES_BASE + 11;
    const ERR_INVALID_PRODUCT_ID  = self::ERROR_CODES_BASE + 12; //
    const ERR_FINISH              = self::ERROR_CODES_BASE + 13;
    const ERR_COMMUNICATION       = self::ERROR_CODES_BASE + 14; // Error while communicating with the server.
    const ERR_SUBSCRIPTIONS_NOT_AVAILABLE = self::ERROR_CODES_BASE + 15; // Subscriptions are not available.
    const ERR_MISSING_TOKEN       = self::ERROR_CODES_BASE + 16; // Purchase information is missing token.
    const ERR_VERIFICATION_FAILED = self::ERROR_CODES_BASE + 17; // Verification of store data failed.
    const ERR_BAD_RESPONSE        = self::ERROR_CODES_BASE + 18; // Verification of store data failed.
    const ERR_REFRESH             = self::ERROR_CODES_BASE + 19; // Failed to refresh the store
    const ERR_PAYMENT_EXPIRED     = self::ERROR_CODES_BASE + 20;
    const ERR_DOWNLOAD            = self::ERROR_CODES_BASE + 21;
    const ERR_SUBSCRIPTION_UPDATE_NOT_AVAILABLE = self::ERROR_CODES_BASE + 22;
    const ERR_PRODUCT_NOT_AVAILABLE = self::ERROR_CODES_BASE + 23; // Error code indicating that the requested product is not available in the store
    const ERR_CLOUD_SERVICE_PERMISSION_DENIED = self::ERROR_CODES_BASE + 24; // Error code indicating that the user has not allowed access to Cloud service information.
    const ERR_CLOUD_SERVICE_NETWORK_CONNECTION_FAILED = self::ERROR_CODES_BASE + 25; // Error code indicating that the device could not connect to the network.
    const ERR_CLOUD_SERVICE_REVOKED = self::ERROR_CODES_BASE + 26; // Error code indicating that the user has revoked permission to use this cloud service.
    const ERR_PRIVACY_ACKNOWLEDGEMENT_REQUIRED = self::ERROR_CODES_BASE + 27; // Error code indicating that the user has not yet acknowledged Apple’s privacy policy for Apple Music.
    const ERR_UNAUTHORIZED_REQUEST_DATA = self::ERROR_CODES_BASE + 28; // Error code indicating that the app is attempting to use a property for which it does not have the required entitlement.
    const ERR_INVALID_OFFER_IDENTIFIER = self::ERROR_CODES_BASE + 29; // Error code indicating that the offer identifier is invalid.
    const ERR_INVALID_OFFER_PRICE = self::ERROR_CODES_BASE + 30; // Error code indicating that the price you specified in App Store Connect is no longer valid.
    const ERR_INVALID_SIGNATURE = self::ERROR_CODES_BASE + 31; // Error code indicating that the signature in a payment discount is not valid.
    const ERR_MISSING_OFFER_PARAMS = self::ERROR_CODES_BASE + 32; // Error code indicating that parameters are missing in a payment discount.



    /**
     * Set the table name associated to this model
     */
    protected $table = 'subscription_transactions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'type',
        'subscription_id',
        'transaction_id',
        'original_transaction_id',
        'purchase_token',
        'signature',
        'receipt',
        'developerPayload',
        'in_app_ownership_type',
        'is_in_intro_offer_period',
        'is_trial_period',
        'subscription_group_identifier',
        'expires_date',
        'original_purchase_date',
        'purchase_date',
        'link_purchase_token'
    ];

    /**
     * Helper function for checking whether transaction is made on Google Play (Android)
     *
     * @return boolean
     */
    public function fromAndroid()
    {
        return $this->type == self::TRANSACTION_ANDROID || $this->type == self::PROVIDER_ANDROID;
    }

    /**
     * Helper function for checking whether transaction is made on App Store (IOS)
     *
     * @return boolean
     */
    public function fromIOS()
    {
        return $this->type == self::TRANSACTION_IOS || $this->type == self::PROVIDER_IOS;
    }

    /**
     * Scope a query that only include in-app purchase-based subscriptions
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSameAs(Builder $query, string $id, string $storeType): Builder
    {
        return $query->where([
            ['transaction_id', $id],
            ['type', $storeType]
        ]);
    }

    /**
     * Get the parent subscription of this transaction
     *
     * @return Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}
