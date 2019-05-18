<?php

namespace Drupal\encrypt_kms\Plugin\KeyType;

use Drupal\Core\Form\FormStateInterface;
use Drupal\key\Plugin\KeyTypeBase;

/**
 * Defines a generic key type for authentication.
 *
 * @KeyType(
 *   id = "aws_kms",
 *   label = @Translation("KMS Key"),
 *   description = @Translation("The ARN of a KMS key"),
 *   group = "encryption",
 *   key_value = {
 *     "plugin" = "aws_kms_arn"
 *   }
 * )
 */
class KmsKeyType extends KeyTypeBase {

  /**
   * {@inheritdoc}
   */
  public static function generateKeyValue(array $configuration) {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function validateKeyValue(array $form, FormStateInterface $form_state, $key_value) {
    // Validation of the key value is optional.
  }

}
