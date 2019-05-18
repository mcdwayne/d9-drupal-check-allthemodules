<?php

namespace Drupal\commerce_recurring_metered\Plugin\Commerce\UsageType;

use Drupal\commerce_recurring\BillingPeriod;
use Drupal\commerce_recurring_metered\SubscriptionFreeUsageInterface;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Tracks and aggregates discrete usage over a period of time.
 *
 * Examples: API calls, mobile phone minutes.
 *
 * @CommerceRecurringUsageType(
 *   id = "counter",
 *   label = @Translation("Counter"),
 * )
 */
class Counter extends UsageTypeBase {

  /**
   * {@inheritdoc}
   */
  public function requiredSubscriptionTypeInterfaces() {
    return [
      SubscriptionFreeUsageInterface::class,
    ];
  }

  /**
   * Add counter usage.
   *
   * Counter usage just needs to be registered for the given start date. The
   * end date argument (if any) is ignored.
   *
   * @param mixed $usage
   *   The usage to add. This must be an array containing the following keys:
   *   - product_variation: The product variation representing each usage.
   *   - quantity (optional): The quantity to add. Defaults to 1.
   * @param \Drupal\Core\Datetime\DrupalDateTime $start
   *   The start date of the usage.
   * @param \Drupal\Core\Datetime\DrupalDateTime|null $end
   *   This parameter is ignored for counter usage.
   */
  public function addUsage($usage, DrupalDateTime $start, DrupalDateTime $end = NULL) {
    if (!isset($usage['product_variation'])) {
      throw new \InvalidArgumentException("You must specify the
      product variation representing this usage (the 'product_variation' key
      in the \$usage array.");
    }

    if (!isset($usage['quantity'])) {
      $usage['quantity'] = 1;
    }

    /** @var \Drupal\commerce_recurring_metered\UsageRecordInterface $record */
    $record = $this->storage->createRecord();
    $record->setUsageType($this->pluginId)
      ->setSubscription($this->subscription)
      ->setProductVariation($usage['product_variation'])
      ->setStartDate($start)
      ->setEndDate($start)
      ->setQuantity($usage['quantity']);

    // Counter usage is simple. We set up the record and store it.
    $this->storage->setRecords([$record]);
  }

  /**
   * {@inheritdoc}
   */
  public function collectCharges(BillingPeriod $period, BillingPeriod $full_period) {
    // Add up all of the counter records, grouping by product variation ID in
    // case someone decided to get fancy.
    $records = $this->usageHistory($period);

    if (empty($records)) {
      return [];
    }

    $variations = [];
    /** @var \Drupal\commerce_recurring_metered\UsageRecordInterface $record */
    foreach ($records as $record) {
      $variationId = $record->getProductVariation()->id();
      $variations[$variationId] = $record->getProductVariation();
    }
    $variationIds = array_unique(array_keys($variations));

    $quantities = array_fill_keys($variationIds, 0);
    foreach ($records as $record) {
      $id = $record->getProductVariation()->id();
      $quantities[$id] += $record->getQuantity();
    }

    // Now we have a set of quantities keyed by product. Use the subscription to
    // get a free quantity for each.
    foreach ($quantities as $variationId => $quantity) {
      /** @var \Drupal\commerce_recurring_metered\SubscriptionFreeUsageInterface $subscription_type */
      $subscription_type = $this->subscription->getType();
      $free_quantity = $subscription_type->getFreeQuantity($variations[$variationId], $this->subscription);
      $quantities[$variationId] = max(0, $quantity - $free_quantity);
    }

    // Now we generate charges.
    $charges = [];

    foreach ($quantities as $variationId => $quantity) {
      if ($quantity) {
        // Counter charges should never be prorated.
        $charges[] = $this->generateCharge($quantity, $variations[$variationId], $full_period, $full_period);
      }
    }

    return $charges;
  }

}
