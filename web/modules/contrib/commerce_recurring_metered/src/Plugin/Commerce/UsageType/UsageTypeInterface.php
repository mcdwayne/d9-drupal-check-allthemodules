<?php

namespace Drupal\commerce_recurring_metered\Plugin\Commerce\UsageType;

use Drupal\commerce_recurring\BillingPeriod;
use Drupal\commerce_recurring\Entity\Subscription;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Usage group plugin type.
 */
interface UsageTypeInterface {

  /**
   * Returns expected interfaces implemented by the subscription type.
   *
   * A subscription type may have to implement certain interface in order to
   * leverage this usage type.
   *
   * This allows usage types to require the subscription type (which also
   * returns the list of usage types) to implement specific logic around
   * free initial quantities which are not required by all usage group
   * types, and which need to be implemented by the subscription type as
   * their logic can vary from usage type to usage type. (The alternative
   * is putting callbacks into the usage type definition.)
   *
   * @return string[]
   *   The list of required interfaces. Note that you should actually use the
   *   magic ::class property with 'use'd classes and let PHP handle namespace
   *   resolution.
   */
  public function requiredSubscriptionTypeInterfaces();

  /**
   * Determines whether to block changing a given property of a subscription.
   *
   * @param string $property
   *   The property which is being changed.
   * @param mixed $currentValue
   *   The current value of the property.
   * @param mixed $newValue
   *   The new proposed value of the property.
   */
  public function enforceChangeScheduling($property, $currentValue, $newValue);

  /**
   * Returns a list of usage records for a given subscription.
   *
   * @param \Drupal\commerce_recurring\BillingPeriod $period
   *   The applicable billing period.
   *
   * @return \Drupal\commerce_recurring_metered\UsageRecordInterface[]
   *   A list of usage records from this billing period for this usage type.
   */
  public function usageHistory(BillingPeriod $period);

  /**
   * Adds usage for this usage group and subscription and recurring order.
   *
   * Because this function's parameters change with each implementation, we
   * declare the interface method with a single variadic parameter, allowing
   * each implementation to override it with its own list of more specific
   * parameters if desired.
   *
   * @param mixed $usage
   *   The usage being added. This can vary widely based on what the usage type
   *   does, so we do not dictate the type. In our default implementations, it
   *   is an array because we need to know which product variation is used to
   *   account for the cost of the usage.
   * @param \Drupal\Core\Datetime\DrupalDateTime $start
   *   The start time for this record.
   * @param \Drupal\Core\Datetime\DrupalDateTime $end
   *   The end time for this record.
   */
  public function addUsage($usage, DrupalDateTime $start, DrupalDateTime $end);

  /**
   * Checks whether usage records are complete for a given subscription.
   *
   * This is usually true, but some subscriptions might need to "wait" on remote
   * services that will record usage data into the system later.
   *
   * @param \Drupal\commerce_recurring\BillingPeriod $period
   *   The applicable billing period.
   *
   * @todo: Actually implement this.
   */
  public function isComplete(BillingPeriod $period);

  /**
   * Returns the charges for this group and a given subscription.
   *
   * @param \Drupal\commerce_recurring\BillingPeriod $period
   *   The billing period for which to collect charges.
   * @param \Drupal\commerce_recurring\BillingPeriod $full_period
   *   The full billing period from the order.
   *
   * @return \Drupal\commerce_recurring\Charge[]
   *   The computed list of charges.
   */
  public function collectCharges(BillingPeriod $period, BillingPeriod $full_period);

  /**
   * React to changes in the subscription.
   *
   * @param \Drupal\commerce_recurring\Entity\Subscription $subscription
   *   The subscription that changed.
   */
  public function onSubscriptionChange(Subscription $subscription);

}
