<?php

/**
 * @file
 * Hooks for Ubercart GoCardless Client module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Respond to webhook received from GoCardless.
 *
 * This hook is called whenever a webhook is received from GoCardless.com.
 *
 * @param array $params
 *  Array containing the webhook event, the Ubercart order ID, and the
 *  contents of the webhook.
 */
function hook_gc_client_webhook($params) {
  
  if ($params['event']['resource_type'] == 'payments') {
     uc_gc_client_webhook_payments([$params]);    
  }
}

/** 
 * Alters mandate details during checkout. 
 * 
 * Allows modules to alter the details of a new mandate on creation of a
 * new order, before it is passed on to GoCardless.com. 
 * 
 * @param array $mandate_details 
 *   Array of mandate details that will be passed to GoCardless. 
 *
 * @param object $order 
 *   The Ubercart order object. 
 */
function hook_gc_client_mandate_details_alter(&$mandate_details, $order) {
  $mandate_details['user']['first_name'] = 'Rob';
}

/**
 * Alter initial payment creation date.
 *
 * Called whilst checkout is completing, only when no other options for
 * an initial payment creation date have been provided.
 *
 * @param null $start_date
 *  
 * @param object $product
 *  The Ubercart product object that has been ordered.
 */
function hook_gc_client_start_date_alter(&$start_date, $product) {
  $start_date = strtotime('+1 month');
}

/** 
 * Alters details of subscription payments during completion of checkout. 
 * 
 * Allows modules to alter the details of the subscription's payments 
 * during completion of checkout, ater customer has been passed 
 * back from GoCardless.com. 
 * 
 * @param array $payment_details 
 *   Array of payment details that will be passed to GoCardless. 
 *
 * @param object $order 
 *   The Ubercart order object. 
 */
function hook_gc_client_subs_payment_alter(&$payment_details, $order) {
  $payment_details['amount'] = $payment_details['amount'] * 2;
}

/** 
 * Alters details of inital one-off payment's during completion of checkout. 
 * 
 * Allows modules to alter the details of the initial one-off payment 
 * during completion of checkout, ater customer has been passed 
 * back from GoCardless.com. 
 * 
 * @param array $payment_details 
 *   Array of payment details that will be passed to GoCardless. 
 *
 * @param object $order 
 *   The Ubercart order object. 
 */
function hook_gc_client_payments_payment_alter(&$payment_details, $order) {
  $payment_details['amount'] = $payment_details['amount'] * 2;
}

/** 
 * Alters details of scheduled payments during cron runs. 
 * 
 * Allows modules to alter the details of either subscription, or one-off 
 * payments during cron runs, before the payment creation is passed to
 * GoCardless.com. 
 * 
 * @param array $payment_details 
 *   Array of payment details that will be passed to GoCardless. 
 *
 * @param object $order 
 *   The Ubercart order object. 
 */
function hook_gc_client_scheduled_payment_alter(&$payment_details, $order) {
  $payment_details['amount'] = $payment_details['amount'] * 2;
}

/** 
 * Alters details of next scheduled payment date during cron runs. 
 * 
 * Allows modules to update the next scheduled payment date for one-off 
 * payments, during cron runs, immediately after a scheduled payment
 * has been created with GoCardless.com. 
 * 
 * @param int $next_payment 
 *   Unix timestamp. 
 *
 * @param object $order 
 *   The Ubercart order object. 
 */
function hook_gc_client_next_scheduled_payment_date_alter(&$next_payment, $order) {
  $next_payment = strtotime('+1 week', $next_payment);
}

/**
 * @} End of "addtogroup hooks".
 */

