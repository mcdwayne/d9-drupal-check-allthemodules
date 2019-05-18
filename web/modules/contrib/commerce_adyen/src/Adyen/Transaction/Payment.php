<?php

namespace Drupal\commerce_adyen\Adyen\Transaction;

use Commerce\Utils\Transaction as TransactionBase;

/**
 * Adyen payment transaction.
 */
class Payment extends TransactionBase {

  /**
   * {@inheritdoc}
   */
  public function authorise($remote_id) {
    $this->setRemoteId($remote_id);
    $this->setStatus(COMMERCE_ADYEN_PAYMENT_STATUS_AUTHORISED);
    $this->setRemoteStatus(COMMERCE_ADYEN_PAYMENT_REMOTE_STATUS_AUTHORISED);
    $this->setMessage('Payment has been successfully authorised.');
  }

  /**
   * {@inheritdoc}
   */
  public function isAuthorised() {
    return $this->getStatus() === COMMERCE_ADYEN_PAYMENT_STATUS_AUTHORISED &&
      $this->getRemoteStatus() === COMMERCE_ADYEN_PAYMENT_REMOTE_STATUS_AUTHORISED;
  }

  /**
   * Pending a transaction.
   *
   * @param string|int $remote_id
   *   Remote identifier of a payment transaction.
   */
  public function pending($remote_id) {
    $this->setRemoteId($remote_id);
    $this->setStatus(COMMERCE_PAYMENT_STATUS_PENDING);
    $this->setRemoteStatus(COMMERCE_ADYEN_PAYMENT_REMOTE_STATUS_PENDING);
    $this->setMessage('Payment has been successfully set to pending');
  }

  /**
   * Check if payment was only pending and waiting for authorised.
   *
   * @return bool
   *   A state of check.
   */
  public function isPending() {
    return $this->getStatus() === COMMERCE_PAYMENT_STATUS_PENDING &&
      $this->getRemoteStatus() === COMMERCE_ADYEN_PAYMENT_REMOTE_STATUS_PENDING;
  }

  /**
   * {@inheritdoc}
   */
  public function fail($remote_id) {
    $this->setRemoteId($remote_id);
    $this->setStatus(COMMERCE_PAYMENT_STATUS_FAILURE);
    $this->setRemoteStatus(COMMERCE_ADYEN_PAYMENT_REMOTE_STATUS_FAILURE);
    $this->setMessage('Payment failed.');
  }

  /**
   * {@inheritdoc}
   */
  public function isFailed() {
    return $this->getStatus() === COMMERCE_PAYMENT_STATUS_FAILURE &&
      $this->getRemoteStatus() === COMMERCE_ADYEN_PAYMENT_REMOTE_STATUS_FAILURE;
  }

  /**
   * {@inheritdoc}
   */
  public function finalize() {
    $this->setStatus(COMMERCE_PAYMENT_STATUS_SUCCESS);
    $this->setRemoteStatus(COMMERCE_ADYEN_PAYMENT_REMOTE_STATUS_CAPTURED);
    $this->setMessage('Payment has been captured and completed.');
  }

  /**
   * {@inheritdoc}
   */
  public function isFinalized() {
    return $this->getStatus() === COMMERCE_PAYMENT_STATUS_SUCCESS &&
      $this->getRemoteStatus() === COMMERCE_ADYEN_PAYMENT_REMOTE_STATUS_CAPTURED;
  }

}
