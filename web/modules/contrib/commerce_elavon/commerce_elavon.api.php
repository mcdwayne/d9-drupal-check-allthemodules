<?php

/**
 * @file
 * Hooks specific to the Commerce Elavon module.
 */

/**
 * @addtogroup commerce_elavon
 * @{
 * The way to implement commerce_elavon hooks.
 *
 * The contents of this file are never loaded, or executed, it is purely for
 * documentation purposes.
 *
 * @link https://www.drupal.org/docs/develop/coding-standards/api-documentation-and-comment-standards#hooks
 * Read the standards for documenting hooks. @endlink
 */

/**
 * Respond to the data to be sent off.
 *
 * This hooks allows modules to manipulate data before they are sent to
 * the remote Converge payment server.
 *
 * @param array &$data
 *   The array that will post to the remote payment gateway server.
 * @param Drupal\commerce_order\Entity\OrderInterface $order
 *   The current order being processed.
 */
function hook_elavon_commerce_elavon_offsite_payment(array &$data, Drupal\commerce_order\Entity\OrderInterface $order) {
  // Add guest name to the form.
  $data['ssl_last_name'] = $order->getBillingProfile()->get('address')->family_name;
  $data['ssl_first_name'] = $order->getBillingProfile()->get('address')->given_name;
}

/**
 * @} End of "addtogroup commerce_elavon".
 */
