<?php

use Drupal\commerce_order\Entity\OrderInterface;

/**
 * Alters order data sent to create contact in mautic.
 *
 * @param $order_data
 * @param $order
 */
function hook_commerce_mautic_order_data_alter(&$order_data, OrderInterface $order) {
  // Alter base data
  $order_data['firstname'] = 'TEST';
  // Add additional fields
  $order_data['order_id'] = $order->id();
}
