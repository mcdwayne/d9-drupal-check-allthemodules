<?php

namespace Drupal\commerce_hyperpay\Transaction\Status;

/**
 * Type used for result codes for rejections due to communication errors.
 */
class RejectedCommunicationError extends Rejected {

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return Constants::TYPE_REJECTED_COMMUNICATION_ERROR;
  }

}
