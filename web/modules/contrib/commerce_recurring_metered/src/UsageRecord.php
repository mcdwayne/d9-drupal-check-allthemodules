<?php

namespace Drupal\commerce_recurring_metered;

use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\commerce_recurring\Entity\SubscriptionInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * Represents a database record for usage tracking.
 */
class UsageRecord implements UsageRecordInterface {

  /**
   * The ID of this usage record, if it has been saved to storage.
   *
   * @var int
   */
  protected $usageId;

  /**
   * The name of the usage group to which this record belongs.
   *
   * @var string
   */
  protected $usageType;

  /**
   * The ID of this record's subscription, if any.
   *
   * @var int
   */
  protected $subscriptionId;

  /**
   * The ID of this record's product variation, if any.
   *
   * @var int
   */
  protected $productVariationId;

  /**
   * The quantity of this record.
   *
   * @var int
   */
  protected $quantity;

  /**
   * The start timestamp of this record.
   *
   * @var \Drupal\Core\Datetime\DrupalDateTime
   */
  protected $start;

  /**
   * The end timestamp of this record.
   *
   * @var \Drupal\Core\Datetime\DrupalDateTime
   */
  protected $end;

  /**
   * The Drupal entity type manager.
   *
   * Used to load subscriptions and products since these database records only
   * store IDs.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $typeManager;

  /**
   * Class-specific constructor which injects the storage service for use later.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $typeManager
   *   The entity type manager.
   * @param \stdClass $values
   *   Values with which to initialize the record.
   */
  public function __construct(EntityTypeManager $typeManager, \stdClass $values = NULL) {
    $this->typeManager = $typeManager;

    $object_map = [
      'usage_id' => 'usageId',
      'usage_type' => 'usageType',
      'subscription_id' => 'subscriptionId',
      'product_variation_id' => 'productVariationId',
    ];

    if (!isset($values)) {
      return;
    }

    foreach ($values as $key => $value) {
      $target = $key;

      if (isset($object_map[$key])) {
        $target = $object_map[$key];
      }

      $this->$target = $value;
    }
  }

  /**
   * Get an array of values for easy insertion via the Drupal database layer.
   *
   * @return array
   *   An array of values this record contains.
   */
  public function getDatabaseValues() {
    return [
      'usage_id' => $this->usageId,
      'usage_type' => $this->usageType,
      'subscription_id' => $this->subscriptionId,
      'product_variation_id' => $this->productVariationId,
      'quantity' => $this->quantity,
      'start' => $this->start,
      'end' => $this->end,
    ];
  }

  /**
   * Get the ID of this record in storage.
   *
   * Used to figure out inserts vs merges in the default database storage
   * implementation.
   *
   * Note: There is no setter for the ID, this can only be set by the storage
   * engine.
   *
   * @return int
   *   The ID of the usage record.
   */
  public function getId() {
    return $this->usageId;
  }

  /**
   * {@inheritdoc}
   */
  public function getUsageType() {
    return $this->usageType;
  }

  /**
   * {@inheritdoc}
   */
  public function setUsageType($usageType) {
    $this->usageType = $usageType;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSubscription() {
    $storage = $this->typeManager->getStorage('commerce_subscription');

    return $storage->load($this->subscriptionId);
  }

  /**
   * {@inheritdoc}
   */
  public function setSubscription(SubscriptionInterface $subscription) {
    $this->subscriptionId = $subscription->id();

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getProductVariation() {
    $storage = $this->typeManager->getStorage('commerce_product_variation');

    return $storage->load($this->productVariationId);
  }

  /**
   * {@inheritdoc}
   */
  public function setProductVariation(ProductVariationInterface $variation) {
    $this->productVariationId = $variation->id();

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuantity() {
    return (int) $this->quantity;
  }

  /**
   * {@inheritdoc}
   */
  public function setQuantity($quantity) {
    $this->quantity = (int) $quantity;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStartDate() {
    return is_null($this->start) ? NULL : new DrupalDateTime("@{$this->start}");
  }

  /**
   * {@inheritdoc}
   */
  public function setStartDate(DrupalDateTime $start) {
    $this->start = $start->getTimestamp();

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStart() {
    return $this->start;
  }

  /**
   * {@inheritdoc}
   */
  public function setStart($start) {
    $this->start = $start;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getEndDate() {
    return $this->end === NULL ? NULL : new DrupalDateTime("@{$this->end}");
  }

  /**
   * {@inheritdoc}
   */
  public function setEndDate(DrupalDateTime $end) {
    $this->end = $end->getTimestamp();

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getEnd() {
    return $this->end;
  }

  /**
   * {@inheritdoc}
   */
  public function setEnd($end) {
    $this->end = $end;

    return $this;
  }

}
