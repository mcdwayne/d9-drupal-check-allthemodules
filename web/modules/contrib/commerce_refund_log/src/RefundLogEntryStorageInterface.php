<?php

namespace Drupal\commerce_refund_log;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;

/**
 * Defines the interface for Refund Log Entries.
 */
interface RefundLogEntryStorageInterface extends ContentEntityStorageInterface {

  /**
   * Loads all refunds for the given payment.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The payment.
   *
   * @return \Drupal\commerce_refund_log\Entity\RefundLogEntryInterface[]
   *   The refunds.
   */
  public function loadMultipleByPayment(PaymentInterface $payment);

}
