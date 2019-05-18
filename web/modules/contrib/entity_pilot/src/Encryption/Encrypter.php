<?php

namespace Drupal\entity_pilot\Encryption;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Encoding;
use Defuse\Crypto\Key;

/**
 * Defines a class for encrypting and decrypting flight contents.
 */
class Encrypter implements EncrypterInterface {

  /**
   * {@inheritdoc}
   */
  public static function encrypt($secret, $data) {
    return Crypto::encrypt($data, Key::loadFromAsciiSafeString($secret));
  }

  /**
   * {@inheritdoc}
   */
  public static function decrypt($secret, $data) {
    return Crypto::decrypt($data, Key::loadFromAsciiSafeString($secret));
  }

  /**
   * {@inheritdoc}
   */
  public static function legacyDecrypt($secret, $data) {
    return Crypto::legacyDecrypt($data, Encoding::hexToBin($secret));
  }

}
