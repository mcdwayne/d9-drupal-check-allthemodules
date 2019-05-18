<?php

/**
 * @file
 * Hooks provided by the Commerce Google Analytics module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter transaction array data for Ecommerce Tracking.
 *
 * @param array $transaction
 *   Transaction array for Ecommerce Tracking to be altered.
 *
 * @param array $context
 *   An array with the following keys:
 *   - order: The order.
 *
 * @see \Drupal\commerce_google_analytics\EventSubscriber\SendOrderAnalyticsSubscriber::buildGaPushParams()
 */
function hook_commerce_google_analytics_transaction_alter(array &$transaction, array $context) {
  $transaction['affiliation'] = 'Custom store or affiliation';
  /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
  $order = $context['order'];
  $transaction['order_id'] = $order->getOrderNumber();
}

/**
 * Alter single item array data for Ecommerce Tracking.
 *
 * @param array $item
 *   An item array for Ecommerce Tracking to be altered.
 * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
 *   The order item.
 * @param array $context
 *   An array with the following keys:
 *   - transaction: transaction array.
 *   - order: The order.
 *
 * @see \Drupal\commerce_google_analytics\EventSubscriber\SendOrderAnalyticsSubscriber::buildGaPushParams()
 */
function hook_commerce_google_analytics_item_alter(array &$item, \Drupal\commerce_order\Entity\OrderItemInterface $order_item, array $context) {
  $item['name'] = $order_item->getTitle();
  /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
  $order = $context['order'];
  $item['order_id'] = $order->getOrderNumber();
}

/**
 * Alter all items array data for Ecommerce Tracking.
 *
 * @param array $item
 *   An item array for Ecommerce Tracking to be altered.
 * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
 *   The order item.
 * @param array $context
 *   An array with the following keys:
 *   - transaction: transaction array.
 *   - order: The order.
 *
 * @see \Drupal\commerce_google_analytics\EventSubscriber\SendOrderAnalyticsSubscriber::buildGaPushParams()
 */
function hook_commerce_google_analytics_items_alter(array &$items, array $context) {
  // Remove the items.
  $items = [];
  /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
  $order = $context['order'];
  foreach ($order->getItems() as $order_item) {
    // Rebuild the items with custom data.
    // ...
  }
}

/**
 * @} End of "addtogroup hooks".
 */
