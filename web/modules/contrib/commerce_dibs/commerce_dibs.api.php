<?php

/**
 * @file
 * Hooks provided by the Commerce DIBS module.
 */

/**
 * Alter the payment data before sending it to DIBS.
 *
 * @param array $data
 *   An array of redirect objects.
 * @param object $order
 *   The Drupal Commerce order object.
 *
 * @see commerce_dibs_payment_data()
 */
function hook_commerce_dibs_payment_data_alter(array &$data, $order) {
  // Set orderId to external service ID for example.
  $external_service = getExternalService();
  $external_service_order_number = $external_service->getOrderId();
  if (!empty($external_service_order_number) && $external_service_order_number != $order->id()) {
    $data['orderId'] = $external_service_order_number;
  }
}
