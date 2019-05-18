<?php

namespace Drupal\commerce_extra_items\Util;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\OrderProcessorInterface;

/**
 * Provides an order processor that removes "extra_item" order item entities.
 */
class CommerceExtraItemsOrderProcessor implements OrderProcessorInterface {

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\commerce_order\OrderRefresh::refresh()
   * @see \Drupal\commerce_promotion\PromotionOrderProcessor::process()
   */
  public function process(OrderInterface $order) {
    foreach ($order->getItems() as $order_item) {
      /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
      if ($order_item->bundle() == 'extra_item') {
        /*
         * Let it work similar to adjustments:
         * During order refresh adjustments are removed and then during
         * processing an order are added again.
         * Since we don't have any other hook in OrderRefresh::refresh()
         * except "order process" - run CommerceExtraItemsOrderProcessor before
         * PromotionOrderProcessor. So "extra_item" order items will be removed
         * and then added again in "Extra items" promotion offer.
         */
        $order->removeItem($order_item);
        $order_item->delete();
      }
    }
  }

}
