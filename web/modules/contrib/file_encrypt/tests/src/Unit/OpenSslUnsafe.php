<?php

namespace Drupal\Tests\file_encrypt\Unit;

/**
 * Provides an unsafe OpenSSL implementation for unit testing.
 */
class OpenSslUnsafe {

  /**
   * The encryption method.
   */
  const METHOD = 'AES-256-CBC';

  /**
   * The encryption password.
   */
  const PASSWORD = 'unsafe password';

  /**
   * Encrypts data.
   *
   * @param string $data
   *   Data to encrypt.
   *
   * @return string
   *   The data encrypted.
   */
  public static function encrypt($data) {
    return \openssl_encrypt($data, self::METHOD, self::PASSWORD, NULL, self::iv());
  }

  /**
   * Decrypts data.
   *
   * @param string $data
   *   Data to decrypt.
   *
   * @return string
   *   The data decrypted.
   */
  public static function decrypt($data) {
    return \openssl_decrypt($data, self::METHOD, self::PASSWORD, NULL, self::iv());
  }

  /**
   * Returns the encryption IV.
   *
   * @return string
   *   An OpenSSL IV string.
   */
  protected static function iv() {
    return str_pad('unsafe iv', 16, "\0");
  }

}
