<?php

namespace Drupal\commerce_recurring_metered;

use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\commerce_recurring\BillingPeriod;
use Drupal\commerce_recurring\Entity\SubscriptionInterface;

/**
 * Interface UsageProxyInterface.
 */
interface UsageProxyInterface {

  /**
   * Record usage of a given type for a given period.
   *
   * @param \Drupal\commerce_recurring\Entity\SubscriptionInterface $subscription
   *   The subscription to which to add the usage.
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface $usage_variation
   *   The variation to be used when calculating the cost of each usage.
   * @param int $quantity
   *   How many usages to add.
   * @param \Drupal\commerce_recurring\BillingPeriod $period
   *   The billing period to which to add the usage. The default is the current
   *   billing period.
   * @param string $usage_type
   *   The ID of the plugin providing usage tracking. The plugin must implement
   *   \Drupal\commerce_recurring\Plugin\Commerce\UsageType\UsageTypeInterface.
   */
  public function addUsage(SubscriptionInterface $subscription, ProductVariationInterface $usage_variation, $quantity = 1, BillingPeriod $period = NULL, $usage_type = 'counter');

  /**
   * Retrieve summarized usage for a particular billing period.
   *
   * @param \Drupal\commerce_recurring\Entity\SubscriptionInterface $subscription
   *   The subscription to check for usage.
   * @param \Drupal\commerce_recurring\BillingPeriod $period
   *   The billing period to check for usage.
   *
   * @return array
   *   A multi-dimensional array containing summarized usage records
   *   for the given period. The array structure is:
   *
   *   First level: Usage type plugin ID.
   *   Second level: Product variation ID (representing the cost of the usage).
   *   Third level: Quantity.
   *
   *   Example:
   *
   *   'counter' => [
   *     1 => 150,
   *     4 => 200,
   *   ],
   *
   *   In this example, 150 usages of the product variation with ID 1 and 200
   *   usages of the product variation with ID 4 have been recorded. They are
   *   using the 'counter' plugin.
   *
   *   You cannot necessarily rely on this data for invoice totals. If you have
   *   to calculate or re-calculate billing for some reason, you should use
   *   plugin-specific methods.
   */
  public function getUsageForPeriod(SubscriptionInterface $subscription, BillingPeriod $period);

  /**
   * Collects usage charges for a subscription's billing period.
   *
   * @param \Drupal\commerce_recurring\Entity\SubscriptionInterface $subscription
   *   The subscription.
   * @param \Drupal\commerce_recurring\BillingPeriod $billing_period
   *   The full billing period from the order.
   *
   * @return \Drupal\commerce_recurring\Charge[]
   *   The charges.
   */
  public function collectCharges(SubscriptionInterface $subscription, BillingPeriod $billing_period);

}
