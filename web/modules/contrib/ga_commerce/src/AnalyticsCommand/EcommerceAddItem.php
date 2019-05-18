<?php

namespace Drupal\ga_commerce\AnalyticsCommand;

use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\ga\AnalyticsCommand\Generic;

/**
 * Defines the ecommerce:addItem command.
 */
class EcommerceAddItem extends Generic {

  const DEFAULT_PRIORITY = -5;

  /**
   * Constructs a new EcommerceAddItem object.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item entity. Will be used to automatically extract all needed
   *   values for the ecommerce:addItem command.
   * @param array $fields_object
   *   A map of values for the command's fieldsObject parameter. This can be
   *   additional values to the ones extracted from the order item entity or
   *   override them.
   * @param string $tracker_name
   *   The tracker name (optional).
   * @param int $priority
   *   The command priority.
   */
  public function __construct(OrderItemInterface $order_item, array $fields_object = [], $tracker_name = NULL, $priority = self::DEFAULT_PRIORITY) {
    $order = $order_item->getOrder();
    $purchased_entity = $order_item->getPurchasedEntity();

    $values = [
      'id' => $order->getOrderNumber() ?: $order->id(),
      'name' => $order_item->getTitle(),
      'quantity' => $order_item->getQuantity(),
      'price' => $order_item->getAdjustedUnitPrice()->getNumber(),
      'currency' => $order_item->getAdjustedUnitPrice()->getCurrencyCode(),
    ];
    if ($purchased_entity->hasField('sku') && !$purchased_entity->get('sku')->isEmpty()) {
      $values['sku'] = $purchased_entity->sku->value;
    }

    // Merge the field values.
    $fields_object += $values;
    parent::__construct('ecommerce:addItem', $fields_object, $tracker_name, $priority);
  }

}
