<?php

namespace Drupal\commerce_opp\Transaction\Status;

/**
 * Type used for result codes for successfully processed transactions.
 */
class Success extends SuccessOrPending {

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return Constants::TYPE_SUCCESS;
  }

}
