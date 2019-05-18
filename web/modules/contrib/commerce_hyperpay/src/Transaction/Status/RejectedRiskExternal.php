<?php

namespace Drupal\commerce_hyperpay\Transaction\Status;

/**
 * Rejected status, due to checks by external risk systems.
 */
class RejectedRiskExternal extends RejectedRisk {

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return Constants::TYPE_REJECTED_RISK_EXTERNAL;
  }

}
