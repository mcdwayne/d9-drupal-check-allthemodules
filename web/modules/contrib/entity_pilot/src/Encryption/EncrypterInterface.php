<?php

namespace Drupal\entity_pilot\Encryption;

/**
 * Defines an interface for encrypting and decrypting.
 */
interface EncrypterInterface {

  /**
   * Encrypts data using the given secret.
   *
   * @param string $secret
   *   Secret key - no need to base64encode - will be encoded by the method.
   * @param string $data
   *   Data to encrypt.
   *
   * @return string
   *   Encrypted content.
   *
   * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
   *   When broken.
   * @throws \Defuse\Crypto\Exception\BadFormatException
   *   When invalid.
   */
  public static function encrypt($secret, $data);

  /**
   * Decrypts data using the given secret.
   *
   * @param string $secret
   *   Secret key - no need to base64encode - will be encoded by the method.
   * @param string $data
   *   Decrypted data.
   *
   * @return string
   *   Decrypted content.
   *
   * @throws \Defuse\Crypto\Exception\BadFormatException
   *   When invalid.
   * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
   *   When broken.
   * @throws \Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException
   *   When wrong key.
   */
  public static function decrypt($secret, $data);

  /**
   * Legacy decrypt.
   *
   * @param string $secret
   *   Secret.
   * @param string $data
   *   Data.
   *
   * @return string
   *   Decrypted content.
   *
   * @throws \Defuse\Crypto\Exception\BadFormatException
   *   When invalid.
   * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
   *   When broken.
   * @throws \Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException
   *   When wrong key.
   */
  public static function legacyDecrypt($secret, $data);

}
