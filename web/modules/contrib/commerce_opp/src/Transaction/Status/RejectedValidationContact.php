<?php

namespace Drupal\commerce_opp\Transaction\Status;

/**
 * Type used for result codes for rejections due to contact validation.
 */
class RejectedValidationContact extends RejectedValidation {

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return Constants::TYPE_REJECTED_VALIDATION_CONTACT;
  }

}
