<?php

namespace Drupal\commerce_opp\Transaction\Status;

/**
 * Type used for result codes for rejections due to format validation.
 */
class RejectedValidationFormat extends RejectedValidation {

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return Constants::TYPE_REJECTED_VALIDATION_FORMAT;
  }

}
