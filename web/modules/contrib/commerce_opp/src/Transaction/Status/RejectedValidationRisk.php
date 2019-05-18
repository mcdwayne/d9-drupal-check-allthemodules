<?php

namespace Drupal\commerce_opp\Transaction\Status;

/**
 * Type used for result codes for rejections due to risk validation.
 */
class RejectedValidationRisk extends RejectedValidation {

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return Constants::TYPE_REJECTED_VALIDATION_RISK;
  }

}
