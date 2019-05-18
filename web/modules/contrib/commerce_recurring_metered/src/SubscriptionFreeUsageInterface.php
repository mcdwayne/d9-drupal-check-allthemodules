<?php

namespace Drupal\commerce_recurring_metered;

use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\commerce_recurring\Entity\SubscriptionInterface;

/**
 * Designates a usage type that supports a set free quantity of usages.
 *
 * Common for prepaid subscriptions, e.g. ones allowing a certain number of API
 * calls per billing period.
 */
interface SubscriptionFreeUsageInterface {

  /**
   * Get the free quantity which corresponds to a given usage charge.
   *
   * This is required by both the Gauge and Counter usage type plugins.
   * Subscription types which want to use usage groups but don't need this
   * behavior can simply return 0.
   *
   * You should return the free usage quantity for an entire billing period.
   * The usage type is responsible for potential prorating (for example, by
   * prorating the charges it generates).
   *
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface $variation
   *   The product variation of the proposed charge.
   * @param \Drupal\commerce_recurring\Entity\SubscriptionInterface $subscription
   *   The subscription to which the charge applies.
   *
   * @return int
   *   The free quantity to be deducted from the proposed charge.
   */
  public function getFreeQuantity(ProductVariationInterface $variation, SubscriptionInterface $subscription);

}
