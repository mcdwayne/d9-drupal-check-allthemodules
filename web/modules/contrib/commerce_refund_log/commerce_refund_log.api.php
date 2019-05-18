<?php

/**
 * @file
 * Document all supported APIs for Commerce Refund Log module.
 */

use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_refund_log\Entity\RefundLogEntryInterface;

/**
 * Alter refund log with payment data before saving.
 *
 * @param \Drupal\commerce_refund_log\Entity\RefundLogEntryInterface $refund_log_entry
 *   Refund log entry which generated from refund payment.
 * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
 *   Commerce refund payment transaction.
 */
function hook_commerce_refund_log_entry_presave_alter(RefundLogEntryInterface &$refund_log_entry, PaymentInterface $payment) {
  if ($payment->bundle() == 'test') {
    $refund_log_entry->setAmount(0);
  }
}
