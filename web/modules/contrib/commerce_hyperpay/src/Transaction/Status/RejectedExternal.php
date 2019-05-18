<?php

namespace Drupal\commerce_hyperpay\Transaction\Status;

/**
 * Type used for rejections by the external bank or similar payment system.
 */
class RejectedExternal extends Rejected {

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return Constants::TYPE_REJECTED_EXTERNAL;
  }

}
