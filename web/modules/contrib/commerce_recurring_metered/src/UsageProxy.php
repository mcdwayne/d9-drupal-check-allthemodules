<?php

namespace Drupal\commerce_recurring_metered;

use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\commerce_recurring\BillingPeriod;
use Drupal\commerce_recurring\Entity\SubscriptionInterface;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * This class simplifies working with usage type plugins.
 *
 * The caller can simply specify the usage type plugin ID rather than having to
 * load it themselves and deal with the idiosyncrasies of usage type plugins.
 *
 * Using this service is not required. You can also just instantiate the plugin
 * you want manually and call the methods directly.
 */
class UsageProxy implements UsageProxyInterface {

  /**
   * The usage type manager.
   *
   * @var \Drupal\commerce_recurring_metered\UsageTypeManager
   */
  protected $usageTypeManager;

  /**
   * Constructs a new UsageProxy object.
   *
   * @param \Drupal\commerce_recurring_metered\UsageTypeManager $usage_type_manager
   *   The usage type manager.
   */
  public function __construct(UsageTypeManager $usage_type_manager) {
    $this->usageTypeManager = $usage_type_manager;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function addUsage(SubscriptionInterface $subscription, ProductVariationInterface $usage_variation, $quantity = 1, BillingPeriod $period = NULL, $usage_type = 'counter') {
    if (!isset($period)) {
      $period = new BillingPeriod(new DrupalDateTime(), new DrupalDateTime());
    }

    $instance = $this->loadUsageType($subscription, $usage_type);
    $instance->addUsage([
      'product_variation' => $usage_variation,
      'quantity' => $quantity,
    ], $period->getStartDate(), $period->getEndDate());
  }

  /**
   * Loads a plugin that can handle usage tracking.
   *
   * @param \Drupal\commerce_recurring\Entity\SubscriptionInterface $subscription
   *   The subscription to add the usage to.
   * @param string $usage_type
   *   The ID of the plugin providing usage tracking.
   *
   * @return \Drupal\commerce_recurring_metered\Plugin\Commerce\UsageType\UsageTypeInterface
   *   The instantiated plugin.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function loadUsageType(SubscriptionInterface $subscription, $usage_type = 'counter') {
    /** @var \Drupal\commerce_recurring_metered\Plugin\Commerce\UsageType\UsageTypeInterface $instance */
    $instance = $this->usageTypeManager->createInstance($usage_type, ['subscription' => $subscription]);
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getUsageForPeriod(SubscriptionInterface $subscription, BillingPeriod $period) {
    $usage_records = [];
    $usage_types = $this->usageTypeManager->getDefinitions();
    foreach ($usage_types as $id => $definition) {
      /** @var \Drupal\commerce_recurring_metered\Plugin\Commerce\UsageType\UsageTypeInterface $usage_type */
      $usage_type = $this->usageTypeManager->createInstance($id, ['subscription' => $subscription]);
      $usage_records[$id] = $usage_type->usageHistory($period);
    }

    return $usage_records;
  }

  /**
   * {@inheritdoc}
   */
  public function collectCharges(SubscriptionInterface $subscription, BillingPeriod $billing_period) {
    $usage_charges = [];

    // Postpaid means we're always charging for the current billing period.
    // The October recurring order (ending on Nov 1st) charges for October.
    $usage_billing_period = $this->adjustBillingPeriod($billing_period, $subscription);
    $full_billing_period = $billing_period;

    // Add charges for metered usage. Metered usage charges are always postpaid.
    $usage_types = $this->usageTypeManager->getDefinitions();
    foreach ($usage_types as $id => $definition) {
      /** @var \Drupal\commerce_recurring_metered\Plugin\Commerce\UsageType\UsageTypeInterface $usage_type */
      $usage_type = $this->usageTypeManager->createInstance($id, ['subscription' => $subscription]);
      $type_charges = $usage_type->collectCharges($usage_billing_period, $full_billing_period);
      foreach ($type_charges as $type_charge) {
        $usage_charges[] = $type_charge;
      }
    }
    return $usage_charges;
  }

  /**
   * Adjusts the billing period to reflect the subscription start/end dates.
   *
   * Copy of \Drupal\commerce_recurring\Plugin\Commerce\SubscriptionType\SubscriptionTypeBase::adjustBillingPeriod().
   *
   * @param \Drupal\commerce_recurring\BillingPeriod $billing_period
   *   The billing period.
   * @param \Drupal\commerce_recurring\Entity\SubscriptionInterface $subscription
   *   The subscription.
   *
   * @return \Drupal\commerce_recurring\BillingPeriod
   *   The adjusted billing period.
   *
   * @see \Drupal\commerce_recurring\Plugin\Commerce\SubscriptionType\SubscriptionTypeBase
   */
  protected function adjustBillingPeriod(BillingPeriod $billing_period, SubscriptionInterface $subscription) {
    $subscription_start_date = $subscription->getStartDate();
    $subscription_end_date = $subscription->getEndDate();
    $start_date = $billing_period->getStartDate();
    $end_date = $billing_period->getEndDate();
    if ($billing_period->contains($subscription_start_date)) {
      // The subscription started after the billing period (E.g: customer
      // subscribed on Mar 10th for a Mar 1st - Apr 1st period).
      $start_date = $subscription_start_date;
    }
    if ($subscription_end_date && $billing_period->contains($subscription_end_date)) {
      // The subscription ended before the end of the billing period.
      $end_date = $subscription_end_date;
    }

    return new BillingPeriod($start_date, $end_date);
  }

}
