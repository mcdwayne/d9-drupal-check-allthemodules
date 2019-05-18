<?php

namespace Drupal\commerce_recurring_metered;

use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\commerce_recurring\BillingPeriod;
use Drupal\commerce_recurring\Entity\SubscriptionInterface;

/**
 * Storage interface for usage records.
 */
interface UsageRecordStorageInterface {

  /**
   * Instantiates a new instance of the usage record class.
   *
   * @return \Drupal\commerce_recurring_metered\UsageRecordInterface
   *   An empty usage record object.
   */
  public function createRecord();

  /**
   * Retrieves usage history for a given billing period.
   *
   * Fetches records which pertain to a given subscription, period, and
   * (optionally) a specific usage variation.
   *
   * @param \Drupal\commerce_recurring\Entity\SubscriptionInterface $subscription
   *   The subscription for which records are being retrieved.
   * @param \Drupal\commerce_recurring\BillingPeriod $period
   *   The billing cycle.
   * @param string $usage_type
   *   The ID of the usage type by which to filter returned records.
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface $variation
   *   The usage variation to filter on.
   *
   * @return \Drupal\commerce_recurring_metered\UsageRecordInterface[]
   *   The usage records.
   */
  public function fetchPeriodRecords(SubscriptionInterface $subscription, BillingPeriod $period, $usage_type = 'counter', ProductVariationInterface $variation = NULL);

  /**
   * Create or update one or more usage records.
   *
   * @param \Drupal\commerce_recurring_metered\UsageRecordInterface[] $records
   *   The usage records to be created or updated.
   */
  public function setRecords(array $records);

  /**
   * Delete one or more usage records.
   *
   * @param \Drupal\commerce_recurring_metered\UsageRecordInterface[] $records
   *   The usage records to be created or updated.
   */
  public function deleteRecords(array $records);

}
