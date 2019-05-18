<?php

namespace Drupal\ga_commerce\AnalyticsCommand;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_price\Price;
use Drupal\ga\AnalyticsCommand\Generic;

/**
 * Defines the ecommerce:addTransaction command.
 */
class EcommerceAddTransaction extends Generic {

  const DEFAULT_PRIORITY = 0;

  /**
   * Constructs a new EcommerceAddTransaction object.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order entity. Will be used to automatically extract all needed values
   *   for the ecommerce:addTransaction command.
   * @param array $fields_object
   *   A map of values for the command's fieldsObject parameter. This can be
   *   additional values to the ones extracted from the order entity or
   *   override them.
   * @param string $tracker_name
   *   The tracker name (optional).
   * @param int $priority
   *   The command priority.
   */
  public function __construct(OrderInterface $order, array $fields_object = [], $tracker_name = NULL, $priority = self::DEFAULT_PRIORITY) {
    $total_price = $order->getTotalPrice();
    $shipping = NULL;
    $tax = NULL;
    if ($total_price) {
      $shipping = new Price('0.0', $total_price->getCurrencyCode());
      $tax = new Price('0.0', $total_price->getCurrencyCode());

      $adjustments = $order->collectAdjustments();
      if ($adjustments) {
        /** @var \Drupal\commerce_order\AdjustmentTransformerInterface $adjustment_transformer */
        $adjustment_transformer = \Drupal::service('commerce_order.adjustment_transformer');
        $adjustments = $adjustment_transformer->combineAdjustments($adjustments);
        $adjustments = $adjustment_transformer->roundAdjustments($adjustments);
        foreach ($adjustments as $adjustment) {
          if ($adjustment->getType() == 'tax') {
            $tax = $tax->add($adjustment->getAmount());
          }
          elseif ($adjustment->getType() == 'shipping') {
            $shipping = $shipping->add($adjustment->getAmount());
          }
        }
      }
    }

    $values = [
      'id' => $order->getOrderNumber() ?: $order->id(),
    ];
    if ($total_price) {
      $values['revenue'] = $total_price->getNumber();
      $values['currency'] = $total_price->getCurrencyCode();
    }
    if ($tax) {
      $values['tax'] = $tax->getNumber();
    }
    if ($shipping) {
      $values['shipping'] = $shipping->getNumber();
    }

    // Merge the field values.
    $fields_object += $values;
    parent::__construct('ecommerce:addTransaction', $fields_object, $tracker_name, $priority);
  }

}
