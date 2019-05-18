<?php

namespace Drupal\commerce_recurring_metered\Plugin\Commerce\UsageType;

use Drupal\commerce_recurring\BillingPeriod;
use Drupal\commerce_recurring_metered\SubscriptionFreeUsageInterface;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Tracks and aggregates usage of active items over a period of time.
 *
 * Examples: hosted virtual servers, mobile phone plan add-ons.
 *
 * @CommerceRecurringUsageType(
 *   id = "gauge",
 *   label = @Translation("Gauge"),
 * )
 */
class Gauge extends UsageTypeBase {

  /**
   * {@inheritdoc}
   */
  public function requiredSubscriptionTypeInterfaces() {
    return [
      SubscriptionFreeUsageInterface::class,
    ];
  }

  /**
   * Ensure there are usage records for the whole cycle.
   *
   * {@inheritdoc}
   */
  public function isComplete(BillingPeriod $period) {
    $records = $this->usageHistory($period);

    // Get the length of the cycle in seconds.
    $interval = $period->getEndDate()
      ->diff($period->getStartDate());
    $period_length = (int) $interval->format('s') + 1;

    // Add up the length of each record. Note that we use usageHistory() here
    // (instead of the storage fetch method) because we want the records which
    // have already been massaged to start and end with the billing cycle start
    // and end timestamps. Otherwise nothing would ever add up.
    $record_length = 0;
    /** @var \Drupal\commerce_recurring_metered\UsageRecordInterface $record */
    foreach ($records as $record) {
      $record_length += $record->getStart() - $record->getEnd() + 1;
    }

    return $period_length === $record_length;
  }

  /**
   * {@inheritdoc}
   */
  public function collectCharges(BillingPeriod $period, BillingPeriod $full_period) {
    $charges = [];

    $records = $this->usageHistory($full_period);
    /** @var \Drupal\commerce_recurring\Plugin\Commerce\SubscriptionType\SubscriptionTypeInterface|\Drupal\commerce_recurring_metered\SubscriptionFreeUsageInterface $subscription_type */
    $subscription_type = $this->subscription->getType();

    // Remove any usage that is free according to the active plan.
    foreach ($records as $index => &$record) {
      $variation = $record->getProductVariation();
      $free_quantity = $subscription_type->getFreeQuantity($variation, $this->subscription);
      $original_quantity = $record->getQuantity();
      if ($original_quantity <= $free_quantity) {
        unset($records[$index]);
      }
      else {
        $record->setQuantity($original_quantity - $free_quantity);

        $charges[] = $this->generateCharge(
          $record->getQuantity(),
          $variation,
          new BillingPeriod($record->getStartDate(), $record->getEndDate()),
          $full_period
        );
      }
    }

    return $charges;
  }

  /**
   * Add gauge usage.
   *
   * Gauge usage needs to make sure to move any overlapping records out of the
   * way so that even bad code cannot deliberately violate the completeness
   * rules.
   *
   * {@inheritdoc}
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

    // Find the order for the current billing period.
    $order = $this->subscription->getCurrentOrder();
    /** @var \Drupal\commerce_recurring_metered\Plugin\Field\FieldType\BillingPeriodItem $field_billing_period */
    $field_billing_period = $order->get('billing_period')->first();
    $period = $field_billing_period->toBillingPeriod();

    // Get all the raw records for this group and subscription.
    $records = $this->storage->fetchPeriodRecords($this->subscription, $period, $this->pluginId, $usage['product_variation']);

    $new_record = $this->storage->createRecord()
      ->setUsageType($this->pluginId)
      ->setSubscription($this->subscription)
      ->setProductVariation($usage['product_variation'])
      ->setStartDate($start)
      ->setQuantity($usage['quantity']);

    if ($end) {
      $new_record->setEndDate($end);
    }

    $start_time = $start->getTimestamp();
    $end_time = $end->getTimestamp();

    // We store arrays of records to update and delete.
    $updates = [$new_record];
    $deletions = [];

    foreach ($records as $record) {
      // The first thing to do is find all records which overlap the new record
      // somehow.
      if (($record->getEnd() >= $start_time || $record->getEnd() === NULL) && $record->getStart() < $start_time) {
        $record->setEnd($start_time);
        $updates[] = $record;
      }

      if ($record->getStart() >= $start_time) {
        // What else we do to preserve sanity depends on whether this is a
        // completed record or not.
        if ($end_time === NULL) {
          // The new record has no end. That means we merely to need to delete
          // all other records which come after it, if any.
          $deletions[] = $record;
        }
        elseif ($record->getEnd() <= $end_time) {
          // The new record has a start and end already. We delete records that
          // are inside of it.
          $deletions[] = $record;
        }
        elseif ($record->getEnd() > $end_time) {
          $record->setStart($end_time + 1);
          $updates[] = $record;
        }
      }
    }

    $this->storage->setRecords($updates);
    $this->storage->deleteRecords($deletions);
  }

}
