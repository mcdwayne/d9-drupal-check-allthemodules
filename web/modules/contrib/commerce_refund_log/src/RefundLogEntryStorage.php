<?php

namespace Drupal\commerce_refund_log;

use Drupal\commerce\CommerceContentEntityStorage;
use Drupal\commerce_payment\Entity\PaymentInterface;

/**
 * Defines the Refund log entry storage.
 */
class RefundLogEntryStorage extends CommerceContentEntityStorage implements RefundLogEntryStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function loadMultipleByPayment(PaymentInterface $payment) {
    $query = $this->getQuery()
      ->condition('payment_id', $payment->id())
      ->sort('refund_log_entry_id');
    $result = $query->execute();

    return $result ? $this->loadMultiple($result) : [];
  }

}
