<?php

namespace Drupal\commerce_rental;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_order\OrderProcessorInterface;

class RentalOrderProcessor implements OrderProcessorInterface {

  protected $rentalRateHelper;

  public function __construct(RentalRateHelper $rental_rate_helper) {
    $this->rentalRateHelper = $rental_rate_helper;
  }

  /**
   * {@inheritdoc}
   */
  public function process(OrderInterface $order) {
    $order_items = $order->getItems();
    foreach ($order_items as $order_item) {
      if ($order_item->hasField('rental_quantity')) {
        $this->calculateRentalRate($order_item);
      }
    }
  }

  /**
   * When a rental order item is saved, calculate and set the price based
   * on the rental variation rental rates.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item.
   */
  protected function calculateRentalRate(OrderItemInterface $order_item) {
    if (!$order_item->isUnitPriceOverridden()) {
      /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $variation */
      $variation = $order_item->getPurchasedEntity();
      // calculate the price based on the variation rental rates
      $rate_manager = $this->rentalRateHelper->setProductVariation($variation);
      $calculated_price = $rate_manager->calculatePrice($order_item);
      // set the price on the order item to the price we calculated above
      if ($calculated_price) {
        $order_item->setUnitPrice($calculated_price, FALSE);
      }
    }
  }
}
