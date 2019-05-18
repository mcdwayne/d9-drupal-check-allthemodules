<?php

namespace Drupal\chinese_identity_card\Plugin\ChineseIdentityCardValidator;

use Drupal\chinese_identity_card\Plugin\ChineseIdentityCardValidatorBase;

/**
 * @ChineseIdentityCardValidator(
 *  id = "null_chinese_identity_card_validator",
 *  description = @Translation("Null validator"),
 * )
 */
class NullChineseIdentityCardValidator extends ChineseIdentityCardValidatorBase {
  /**
   * @inheritdoc
   */
  public function validate($value) {
    return TRUE;
  }
}