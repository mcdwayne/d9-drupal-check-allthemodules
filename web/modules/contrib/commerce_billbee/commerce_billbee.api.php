<?php

/**
 * @file
 * Hooks provided by the Commerce Billbee module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter fields on an order when synchronising with Billbee.
 *
 * Modules may implement this hook to add extra info on an order, or overwrite
 * existing info.
 *
 * @param array $billbee_order
 *   The order response which is sent to Billbee using shop sync.
 * @param \Drupal\commerce_order\Entity\OrderInterface $order
 *   The Commerce order being handled.
 */
function hook_commerce_billbee_order_alter(array &$billbee_order, \Drupal\commerce_order\Entity\OrderInterface $commerce_order) {

  // Example: Map phone1 with custom phone field on billing profile.
  $billbee_order['phone1'] =  $commerce_order->getBillingProfile()->get('phone')->value;
}

/**
 * Alter fields on a product when synchronising with Billbee.
 *
 * Modules may implement this hook to add extra info on a product, or overwrite
 * existing info.
 *
 * @param array $billbee_product
 *   The product response which is sent to Billbee using shop sync.
 * @param \Drupal\commerce_product\Entity\ProductVariationInterface $commerce_product_variation
 *   The Commerce product variation being handled.
 */
function hook_commerce_billbee_product_alter(array &$billbee_product, \Drupal\commerce_product\Entity\ProductVariationInterface $commerce_product_variation) {

  // Example: Map the product description with the commerce product body field.
  $billbee_product['description'] = $commerce_product_variation->getProduct()->get('body')->value;
}

/**
 * @} End of "addtogroup hooks".
 */
