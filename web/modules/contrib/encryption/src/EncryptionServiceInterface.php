<?php

namespace Drupal\encryption;

/**
 * Interface EncryptionServiceInterface.
 *
 * @package Drupal\encryption
 */
interface EncryptionServiceInterface {

  /**
   * Encrypt a value using the encryption key from settings.php.
   *
   * @param string $value
   *   The value tobe encrypted.
   * @param bool $raw_output
   *   Should be set to TRUE if a raw output value is required. Otherwise, a
   *   url safe base64 encoded encoded string will be returned.
   *
   * @return string|null
   *   A Base64 encoded representation of the encrypted value or null if
   *   encryption fails for some reason.
   */
  public function encrypt($value, $raw_output = FALSE);

  /**
   * Decrypt a value using the encryption key from settings.php.
   *
   * @param string $value
   *   An encrypted string.
   * @param bool $raw_input
   *   Should be set to TRUE if the input value is not a base64 encoded/url safe
   *   string (Defaults to FALSE).
   *
   * @return string|null
   *   The decrypted value or null if decryption fails.
   */
  public function decrypt($value, $raw_input = FALSE);

  /**
   * Gets the `$settings['encryption_key']` value from settings.php.
   *
   * @return string|null
   *   The encryption key or null if validation fails.
   */
  public function getEncryptionKey();

}
