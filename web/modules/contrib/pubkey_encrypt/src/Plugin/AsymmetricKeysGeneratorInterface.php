<?php

namespace Drupal\pubkey_encrypt\Plugin;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Defines an interface for ice cream flavor plugins.
 */
interface AsymmetricKeysGeneratorInterface extends ContainerFactoryPluginInterface, PluginInspectionInterface, ConfigurablePluginInterface {

  /**
   * Return name of the asymmetric keys generator plugin.
   *
   * @return string
   */
  public function getName();

  /**
   * Return description of the asymmetric keys generator plugin.
   *
   * @return string
   */
  public function getDescription();

  /**
   * Generate and return asymmetric keys.
   *
   * @return string[]|NULL
   *   Array of strings indexed with 'public_key' and 'private_key'.
   */
  public function generateAsymmetricKeys();

  /**
   * Return encrypted data.
   *
   * @return string
   */
  public function encryptWithPublicKey($original_data, $public_key);

  /**
   * Return decrypted data.
   *
   * @return string
   */
  public function decryptWithPrivateKey($encrypted_data, $private_key);

}
