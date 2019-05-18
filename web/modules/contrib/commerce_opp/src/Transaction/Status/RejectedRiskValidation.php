<?php

namespace Drupal\commerce_opp\Transaction\Status;

/**
 * Type used for result codes for rejections due to risk validation.
 */
class RejectedRiskValidation extends RejectedRisk {

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return Constants::TYPE_REJECTED_RISK_VALIDATION;
  }

}
