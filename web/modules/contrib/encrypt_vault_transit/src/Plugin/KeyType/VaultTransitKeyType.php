<?php

namespace Drupal\encrypt_vault_transit\Plugin\KeyType;

use Drupal\Core\Form\FormStateInterface;
use Drupal\key\Plugin\KeyTypeBase;

/**
 * Defines a generic key type for authentication.
 *
 * @KeyType(
 *   id = "vault_transit",
 *   label = @Translation("Vault Transit Key"),
 *   description = @Translation("The name of a Vault Transit key"),
 *   group = "encryption",
 *   key_value = {
 *     "plugin" = "vault_transit_key"
 *   }
 * )
 */
class VaultTransitKeyType extends KeyTypeBase {

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
