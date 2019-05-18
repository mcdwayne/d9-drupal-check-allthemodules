<?php

namespace Drupal\commerce_opp\Transaction\Status;

/**
 * Type used for result codes for pending transactions.
 */
class Pending extends SuccessOrPending {

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return Constants::TYPE_PENDING;
  }

}
