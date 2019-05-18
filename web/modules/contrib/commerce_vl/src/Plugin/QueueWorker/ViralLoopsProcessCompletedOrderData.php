<?php

namespace Drupal\commerce_vl\Plugin\QueueWorker;

/**
 * Sends delayed Viral Loops tracking event.
 *
 * @QueueWorker(
 *   id = "viral_loops_process_completed_order_data",
 *   title = @Translation("Viral Loops process completed order data"),
 *   cron = {"time" = 60}
 * )
 */
class ViralLoopsProcessCompletedOrderData extends ViralLoopsRequestBase {

  /**
   * {@inheritdoc}
   */
  protected function getMethodName() {
    return 'processCompletedOrderData';
  }

}
