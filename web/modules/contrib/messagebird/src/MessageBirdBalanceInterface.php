<?php

namespace Drupal\messagebird;

/**
 * Interface MessageBirdBalanceInterface.
 *
 * @package Drupal\messagebird
 */
interface MessageBirdBalanceInterface {

  /**
   * Get amount.
   */
  public function getAmount();

  /**
   * Get type.
   */
  public function getType();

  /**
   * Get payment.
   */
  public function getPayment();

}
