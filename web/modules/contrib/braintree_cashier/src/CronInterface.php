<?php

namespace Drupal\braintree_cashier;

/**
 * Provides the interface for the Braintree Cashier module's cron.
 */
interface CronInterface {

  /**
   * Runs the cron.
   */
  public function run();

}
