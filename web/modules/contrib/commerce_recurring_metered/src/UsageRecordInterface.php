<?php

namespace Drupal\commerce_recurring_metered;

use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\commerce_recurring\Entity\SubscriptionInterface;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Usage records are used to track metered usage.
 *
 * They contain information about the subscription, the product variation used
 * to charge for the usage, the type of usage, the quantity, and when the usage
 * occurred. Usage types read this information in order to tell Commerce
 * Recurring what charge(s) to add to the order.
 */
interface UsageRecordInterface {

  /**
   * Get the usage type to which this record belongs.
   *
   * @return string
   *   The usage type.
   */
  public function getUsageType();

  /**
   * Set the usage type to which this record belongs.
   *
   * @param string $usageType
   *   The ID of the usage type.
   *
   * @return static
   */
  public function setUsageType($usageType);

  /**
   * Get the subscription with which this record is associated.
   *
   * @return \Drupal\commerce_recurring\Entity\SubscriptionInterface
   *   The subscription.
   */
  public function getSubscription();

  /**
   * Set the subscription with which this record is associated.
   *
   * @param \Drupal\commerce_recurring\Entity\SubscriptionInterface $subscription
   *   The subscription.
   *
   * @return static
   */
  public function setSubscription(SubscriptionInterface $subscription);

  /**
   * Get the product variation which this record will charge for.
   *
   * @return \Drupal\commerce_product\Entity\ProductVariationInterface
   *   The product variation.
   */
  public function getProductVariation();

  /**
   * Set the product variation which this record will charge for.
   *
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface $variation
   *   The product variation.
   *
   * @return static
   */
  public function setProductVariation(ProductVariationInterface $variation);

  /**
   * Get the quantity of this record.
   *
   * @return int
   *   The quantity.
   */
  public function getQuantity();

  /**
   * Set the quantity of this record.
   *
   * @param int $quantity
   *   The quantity.
   *
   * @return static
   */
  public function setQuantity($quantity);

  /**
   * Get the start time of this record.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   *   The start date.
   */
  public function getStartDate();

  /**
   * Set the start time of this record.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $start
   *   The start date.
   *
   * @return static
   */
  public function setStartDate(DrupalDateTime $start);

  /**
   * Get the start timestamp of this record.
   *
   * @return int
   *   The start timestamp.
   */
  public function getStart();

  /**
   * Set the start timestamp of this record.
   *
   * @param int|null $start
   *   The start timestamp.
   *
   * @return static
   */
  public function setStart($start);

  /**
   * Get the end time of this record.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   *   The end date.
   */
  public function getEndDate();

  /**
   * Set the end time of this record.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $end
   *   The end date.
   *
   * @return static
   */
  public function setEndDate(DrupalDateTime $end);

  /**
   * Get the end timestamp of this record.
   *
   * @return int
   *   The end timestamp.
   */
  public function getEnd();

  /**
   * Set the end timestamp of this record.
   *
   * @param int|null $end
   *   The timestamp when the record ended.
   *
   * @return static
   */
  public function setEnd($end);

}
