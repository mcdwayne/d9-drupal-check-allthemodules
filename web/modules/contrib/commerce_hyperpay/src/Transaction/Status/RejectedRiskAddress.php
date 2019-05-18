<?php

namespace Drupal\commerce_hyperpay\Transaction\Status;

/**
 * Type used for result codes for rejections due to address validation.
 */
class RejectedRiskAddress extends RejectedRisk {

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return Constants::TYPE_REJECTED_RISK_ADDRESS;
  }

}
