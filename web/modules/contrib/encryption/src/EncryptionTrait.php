<?php

namespace Drupal\encryption;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Site\Settings;

/**
 * Provides basic encryption/decryption methods.
 *
 * Used to encrypt and decrypt text using the 'AES-256-CTR' encryption method
 * using the openssl library that in comes with php unless omitted during
 * compilation.
 *
 * This trait uses an encryption key that should be added to the `$settings
 * array in settings.php. i.e. `$settings['encryption_key']='foo...bar';`
 *
 * An encryption key is a 32 bit binary value that is base63 encoded. On a Mac
 * or linux system, A random encryption key can be created with
 * `dd bs=1 count=32 if=/dev/urandom | openssl base64`.
 *
 * Site instances that share config should use the same encryption key.
 *
 * @package Drupal\encryption
 */
trait EncryptionTrait {

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
  public function encrypt($value, $raw_output = FALSE) {
    // Get the encryption key.
    if ($key = $this->getEncryptionKey()) {
      // Generates a random initialization vector.
      $iv = Crypt::randomBytes(16);
      // Generate a HMAC key using the initialization vector as a salt.
      $h_key = hash_hmac('sha256', hash('sha256', substr($key, 16), TRUE), hash('sha256', substr($iv, 8), TRUE), TRUE);
      // Concatenate the initialization vector and the encrypted value.
      $cypher = '03' . $iv . openssl_encrypt($value, 'AES-256-CTR', $key, TRUE, $iv);
      // Encode and concatenate the hmac, format code and cypher.
      $message = hash_hmac('sha256', $cypher, $h_key, TRUE) . $cypher;
      // Modify the message so it's safe to use in URLs.
      return $raw_output ? $message : str_replace(
        ['+', '/', '='],
        ['-', '_', ''],
        base64_encode($message)
      );
    }
  }

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
  public function decrypt($value, $raw_input = FALSE) {
    // Get the encryption key.
    if (!empty($value) && $key = $this->getEncryptionKey()) {
      // Reverse the urls-safe replacement and decode.
      $message = $raw_input ? $value : base64_decode(str_replace(['-', '_'], ['+', '/'], $value));
      // Get the cypher hash.
      $hmac = substr($message, 0, 32);
      // Decode the initialization vector.
      $iv = substr($message, 34, 16);
      // Re generate the HMAC key.
      $h_key = hash_hmac('sha256', hash('sha256', substr($key, 16), TRUE), hash('sha256', substr($iv, 8), TRUE), TRUE);
      if (Crypt::hashEquals($hmac, hash_hmac('sha256', substr($message, 32), $h_key, TRUE))) {
        // Decrypt to supplied value.
        return openssl_decrypt(substr($message, 50), 'AES-256-CTR', $key, TRUE, $iv);
      }
    }
  }

  /**
   * Gets the `$settings['encryption_key']` value from settings.php.
   *
   * @return string|null
   *   The encryption key or null if validation fails.
   */
  public function getEncryptionKey() {
    $key = base64_decode(Settings::get('encryption_key'));

    // Make sure the key is the correct size.
    if (strlen($key) === 32) {
      return $key;
    }
  }

}
