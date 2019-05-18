<?php

namespace Drupal\commerce_opp\Transaction\Status;

/**
 * Type used for result codes for rejections due to error in async workflow.
 */
class RejectedAsyncError extends Rejected {

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return Constants::TYPE_REJECTED_ASYNC_ERROR;
  }

}
