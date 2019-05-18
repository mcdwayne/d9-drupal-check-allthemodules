<?php

namespace Drupal\commerce_prorater_stepped_proportional;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\commerce_order\Adjustment;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\OrderProcessorInterface;
use Drupal\commerce_recurring\Entity\BillingScheduleInterface;

/**
 * Adds a zero adjustment to show that there is a free rollover interval.
 */
class RolloverAdjustmentOrderProcessor implements OrderProcessorInterface {

  /**
   * The time.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs a new InitialOrderProcessor object.
   *
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time.
   */
  public function __construct(TimeInterface $time) {
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public function process(OrderInterface $order) {
    // Repeat the work of \Drupal\commerce_recurring\InitialOrderProcessor to
    // see whether there is a free rollover period.
    if ($order->bundle() == 'recurring') {
      return;
    }

    $start_date = DrupalDateTime::createFromTimestamp($this->time->getRequestTime());
    foreach ($order->getItems() as $order_item) {
      $purchased_entity = $order_item->getPurchasedEntity();
      if (!$purchased_entity || !$purchased_entity->hasField('billing_schedule')) {
        continue;
      }
      /** @var \Drupal\commerce_recurring\Entity\BillingScheduleInterface $billing_schedule */
      $billing_schedule = $purchased_entity->get('billing_schedule')->entity;
      if (!$billing_schedule) {
        continue;
      }

      if ($billing_schedule->getBillingType() != BillingScheduleInterface::BILLING_TYPE_PREPAID) {
        continue;
      }

      if ($billing_schedule->getPluginId() != 'fixed_with_free_rollover') {
        // Skip if not our billing schedule plugin.
        continue;
      }

      if ($billing_schedule->getPlugin()->firstPeriodHasRollover($start_date)) {
        $billing_schedule_plugin_configuration = $billing_schedule->getPluginConfiguration();

        // Add an adjustment of 0 to show the customer they're getting the free
        // rollover month.
        $order_item->addAdjustment(new Adjustment([
          'type' => 'rollover',
          'label' => t('Free rollover @period', [
            // TODO: use the human-readable string from the interval plugin
            // definition.
            // TODO: this is assuming the interval duration is 1!!!
            '@period' => $billing_schedule_plugin_configuration['rollover_interval']['period'],
          ]),
          'amount' => $order_item->getUnitPrice()->multiply('0'),
          'source_id' => $billing_schedule->id(),
        ]));
      }
    }
  }

}
