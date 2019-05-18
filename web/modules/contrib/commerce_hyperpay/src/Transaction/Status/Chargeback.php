<?php

namespace Drupal\commerce_hyperpay\Transaction\Status;

/**
 * Status used for chargeback related result codes.
 */
class Chargeback extends AbstractStatus {

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return Constants::TYPE_CHARGEBACK;
  }

}
