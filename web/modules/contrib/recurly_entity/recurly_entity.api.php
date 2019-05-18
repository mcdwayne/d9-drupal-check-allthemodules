<?php

/**
 * Modify a subscription before it is created in Recurly. This can be used to
 * apply addons or coupons to a subscription before it is saved. This hook is
 * only called when the
 * RecurlySubscriptionController::createRecurlySubscription() method is called
 * to create the subscription, as we can't directly hook into the Recurly PHP
 * library.
 *
 * @param Recurly_Subscription $subscription
 *   The subscription that is about to be created.
 */
function hook_recurly_subscription_precreate(Recurly_Subscription $subscription) {
  // Load the Drupal account associated with this subscription.
  $account = user_load(recurly_account_load(['account_code' => $subscription->account->account_code], TRUE)->entity_id);

  // Always give the admin user an amazing discount.
  if ($account->id == 1) {
    $coupon = Recurly_Coupon::get('big-discount');
    $subscription->coupon_code = $coupon->coupon_code;
  }
}

/**
 * Modify a subscription after it is created in Recurly. This hook is only
 * called when the
 * RecurlySubscriptionController::createRecurlySubscription() method is called
 * to create the subscription, as we can't directly hook into the Recurly PHP
 * library.
 *
 * @param Recurly_Subscription $subscription
 *   The subscription that has been created.
 */
function hook_recurly_subscription_create(Recurly_Subscription $subscription) {
}
