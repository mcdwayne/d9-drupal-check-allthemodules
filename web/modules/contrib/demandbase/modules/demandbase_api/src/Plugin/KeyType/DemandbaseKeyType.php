<?php

namespace Drupal\demandbase_api\Plugin\KeyType;

use Drupal\Core\Form\FormStateInterface;
use Drupal\key\Plugin\KeyTypeBase;

/**
 * Defines a generic key type for authentication.
 *
 * @KeyType(
 *   id = "demandbase_api",
 *   label = @Translation("Demandbase API"),
 *   description = @Translation("API Key provided by Demandbase"),
 *   group = "demandbase",
 *   key_value = {
 *     "plugin" = "text_field"
 *   }
 * )
 */
class DemandbaseKeyType extends KeyTypeBase {

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
