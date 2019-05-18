<?php

namespace Drupal\commerce_opp\Transaction\Status;

/**
 * Type used for result codes for rejections due to blacklist validation.
 */
class RejectedRiskBlacklist extends RejectedRisk {

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return Constants::TYPE_REJECTED_RISK_BLACKLIST;
  }

}
