<?php

namespace Drupal\commerce_adyen\Adyen;

/**
 * Refund request.
 */
class Refund extends Modification {

  /**
   * {@inheritdoc}
   */
  public function __construct($order) {
    parent::__construct($order, '', self::REFUND);
  }

  /**
   * {@inheritdoc}
   */
  public function isAvailable() {
    return !$this->transaction->isFinalized() || empty($this->transaction->getRemoteId());
  }

}
