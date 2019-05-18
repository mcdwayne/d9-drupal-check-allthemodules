<?php

namespace Drupal\commerce_opp\Transaction\Status;

/**
 * Type used for result codes for rejections due to registration validation.
 */
class RejectedValidationRegistration extends RejectedValidation {

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return Constants::TYPE_REJECTED_VALIDATION_REGISTRATION;
  }

}
