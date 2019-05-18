<?php

namespace Drupal\commerce_opp\Transaction\Status;

/**
 * Type used for result codes for rejections due to system errors.
 */
class RejectedSystemError extends Rejected {

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return Constants::TYPE_REJECTED_SYSTEM_ERROR;
  }

}
