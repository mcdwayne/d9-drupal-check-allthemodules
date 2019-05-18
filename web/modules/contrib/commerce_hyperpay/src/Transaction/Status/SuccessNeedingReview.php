<?php

namespace Drupal\commerce_hyperpay\Transaction\Status;

/**
 * Type used for successfully processed transactions needing manual review.
 */
class SuccessNeedingReview extends SuccessOrPending {

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return Constants::TYPE_SUCCESS_NEEDING_REVIEW;
  }

}
