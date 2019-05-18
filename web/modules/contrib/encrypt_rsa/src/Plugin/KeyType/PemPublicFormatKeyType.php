<?php

namespace Drupal\encrypt_rsa\Plugin\KeyType;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\key\Exception\KeyException;
use Drupal\key\Plugin\KeyTypeBase;
use Drupal\key\Plugin\KeyPluginFormInterface;

/**
 * Defines a generic key type for encryption.
 *
 * @KeyType(
 *   id = "pem_public",
 *   label = @Translation("Public key"),
 *   description = @Translation("A public key type to using PEM format."),
 *   group = "encryption",
 *   key_value = {
 *     "plugin" = "textarea_field"
 *   }
 * )
 */
class PemPublicFormatKeyType extends PemFormatKeyTypeBase implements KeyPluginFormInterface {

  /**
   * {@inheritdoc}
   */
  protected function getKeyDetails($key_value) {
    $key = openssl_get_publickey($key_value);
    return $key ? openssl_pkey_get_details($key) : FALSE;
  }

}
