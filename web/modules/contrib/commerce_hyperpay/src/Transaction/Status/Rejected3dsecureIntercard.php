<?php

namespace Drupal\commerce_hyperpay\Transaction\Status;

/**
 * Type used for rejections due to 3Dsecure and Intercard risk checks.
 */
class Rejected3dsecureIntercard extends Rejected {

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return Constants::TYPE_REJECTED_3DSECURE_INTERCARD;
  }

}
