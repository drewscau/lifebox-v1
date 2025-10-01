<?php

namespace App\Models;

class NotificationType
{
    // A subscription was recovered from account hold.
    const SUBSCRIPTION_RECOVERED  = [
        "title" => "Subscription Recovered",
        "text" => "Your form of payment was updated and your Lifebox subscription has been recovered."
    ];
    // An active subscription was renewed.
    const SUBSCRIPTION_RENEWED  = 2;
    // A subscription was either voluntarily or involuntarily cancelled. For voluntary cancellation, sent when the user cancels.
    const SUBSCRIPTION_CANCELED  = 3;
    // A new subscription was purchased.
    const SUBSCRIPTION_PURCHASED  = 4;
    // A subscription has entered account hold (if enabled).
    const SUBSCRIPTION_ON_HOLD  = [
        "title" => "Subscription on hold",
        "text" => "To regain access to Lifebox, please update your payment method. Click here to go to the Google Play subscription settings"
    ];
    // A subscription has entered grace period (if enabled).
    const SUBSCRIPTION_IN_GRACE_PERIOD  = [
        "title" => "Payment Declined",
        "text" => "To keep your subscription to Lifebox, please update your account payment information."
    ];
    // User has reactivated their subscription from Play > Account > Subscriptions (requires opt-in for subscription restoration).
    const SUBSCRIPTION_RESTARTED   = 7;
    // A subscription price change has successfully been confirmed by the user.
    const SUBSCRIPTION_PRICE_CHANGE_CONFIRMED   = 8;
    // A subscription's recurrence time has been extended.
    const SUBSCRIPTION_DEFERRED   = 9;
    // A subscription has been paused.
    const SUBSCRIPTION_PAUSED   = [
        "title" => "Lifebox subscription is paused",
        "text" => "Your payments for Lifebox are paused and will automatically resume on base on the set time. You can manually resume your subscription at any time."
    ];
    // A subscription pause schedule has been changed.
    const SUBSCRIPTION_PAUSE_SCHEDULE_CHANGED   = [
        "title" => "Lifebox subscription will be paused",
        "text" => "Your payments for Lifebox will be paused base on the set time. You can manually resume your subscription at any time."
    ];
    // A subscription has been revoked from the user before the expiration time.
    const SUBSCRIPTION_REVOKED   = 12;
    // A subscription has expired.
    const SUBSCRIPTION_EXPIRED    = 13;


    // Indicates that Apple Support canceled the auto-renewable subscription and the customer received a refund as of the timestamp in cancellation_date_ms.
    const CANCEL  = "CANCEL";
    // Indicates that the customer initiated a refund request for a consumable in-app purchase, and the App Store is requesting that you provide consumption data.
    const CONSUMPTION_REQUEST  = "CONSUMPTION_REQUEST";
    // Indicates that the customer made a change in their subscription plan that takes effect at the next renewal. The currently active plan isn’t affected.
    const DID_CHANGE_RENEWAL_PREF  = "DID_CHANGE_RENEWAL_PREF";
    // Indicates a change in the subscription renewal status. In the JSON response, check auto_renew_status_change_date_ms to know the date and time of the last status update. Check auto_renew_status to know the current renewal status.
    const DID_CHANGE_RENEWAL_STATUS = "DID_CHANGE_RENEWAL_STATUS";
    // Indicates a subscription that failed to renew due to a billing issue. Check is_in_billing_retry_period to know the current retry status of the subscription. Check grace_period_expires_date to know the new service expiration date if the subscription is in a billing grace period.
    const DID_FAIL_TO_RENEW = "DID_FAIL_TO_RENEW";
    // Indicates a successful automatic renewal of an expired subscription that failed to renew in the past. Check expires_date to determine the next renewal date and time.
    const DID_RECOVER = "DID_RECOVER";
    // Indicates that a customer’s subscription has successfully auto-renewed for a new transaction period.
    const DID_RENEW = "DID_RENEW";
    // Occurs at the user’s initial purchase of the subscription. Store latest_receipt on your server as a token to verify the user’s subscription status at any time by validating it with the App Store.
    const INITIAL_BUY = "INITIAL_BUY";
    // Indicates the customer renewed a subscription interactively, either by using your app’s interface, or on the App Store in the account’s Subscriptions settings. Make service available immediately.
    const INTERACTIVE_RENEWAL = "INTERACTIVE_RENEWAL";
    // Indicates that App Store has started asking the customer to consent to your app’s subscription price increase. In the unified_receipt.Pending_renewal_info object, the price_consent_status value is 0, indicating that App Store is asking for the customer’s consent, and hasn’t received it. The subscription won’t auto-renew unless the user agrees to the new price. When the customer agrees to the price increase, the system sets price_consent_status to 1. Check the receipt using verifyReceipt to view the updated price-consent status.
    const PRICE_INCREASE_CONSENT = "PRICE_INCREASE_CONSENT";
    // Indicates that the App Store successfully refunded a transaction for a consumable in-app purchase, a non-consumable in-app purchase, or a non-renewing subscription. The cancellation_date_ms contains the timestamp of the refunded transaction. The original_transaction_id and product_id identify the original transaction and product. The cancellation_reason contains the reason.
    const REFUND = "REFUND";
    // Indicates that an in-app purchase the user was entitled to through Family Sharing is no longer available through sharing. StoreKit sends this notification when a purchaser disabled Family Sharing for a product, the purchaser (or family member) left the family group, or the purchaser asked for and received a refund. Your app will also receive a paymentQueue(_:didRevokeEntitlementsForProductIdentifiers:) call. For more information about Family Sharing, see Supporting Family Sharing in Your App.
    const REVOKE = "REVOKE";

    // RENEWAL (DEPRECATED)
    // As of March 10, 2021 this notification is no longer sent in production and sandbox environments.
    // Update your existing code to rely on the DID_RECOVER notification type instead.
}
