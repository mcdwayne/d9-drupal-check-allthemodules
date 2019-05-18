<?php

namespace Drupal\commerce_adyen\Adyen;

/**
 * Capture request.
 */
class Capture extends Modification {

  /**
   * {@inheritdoc}
   */
  public function __construct($order) {
    parent::__construct($order, COMMERCE_ADYEN_PAYMENT_REMOTE_STATUS_AUTHORISED, self::CAPTURE);

    $this->transaction->setAmount($this->transaction->getOrder()->commerce_order_total->amount->value());
  }

  /**
   * {@inheritdoc}
   */
  public function isAvailable() {
    return $this->transaction->isAuthorised() && !empty($this->transaction->getRemoteId());
  }

}
