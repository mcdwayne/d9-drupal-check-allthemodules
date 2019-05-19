<?php

namespace Drupal\webfactory\Services;

/**
 * Provides security helpers : token generate, crypt, decrypt.
 */
class Security {

  /**
   * Cryptographic algorithm.
   *
   * @var string
   */
  private $cipher = MCRYPT_RIJNDAEL_256;

  /**
   * Cryptographic mode.
   *
   * @var string
   */
  private $mode = 'cbc';

  /**
   * Returns an encrypted data.
   *
   * @param string $data
   *   Text to encrypt.
   *
   * @return string
   *   Given text encrypted.
   */
  public function crypt($data, $token) {
    $key_hash = md5($token);
    $key = substr($key_hash, 0,
      mcrypt_get_key_size($this->cipher, $this->mode));
    $iv  = substr($key_hash, 0,
      mcrypt_get_block_size($this->cipher, $this->mode));

    $data = mcrypt_encrypt($this->cipher, $key, $data, $this->mode, $iv);

    return base64_encode($data);
  }

  /**
   * Returns the decrypted data.
   *
   * @param string $data
   *   Text to decrypt.
   *
   * @return string
   *   Clear text.
   */
  public function decrypt($data, $token) {
    $key_hash = md5($token);
    $key = substr($key_hash, 0,
      mcrypt_get_key_size($this->cipher, $this->mode));
    $iv  = substr($key_hash, 0,
      mcrypt_get_block_size($this->cipher, $this->mode));

    $data = base64_decode($data);
    $data = mcrypt_decrypt($this->cipher, $key, $data, $this->mode, $iv);

    return rtrim($data);
  }

  /**
   * Generate a random token.
   *
   * @return string
   *   A random token.
   */
  public function generateToken() {
    $token = bin2hex(openssl_random_pseudo_bytes(16));
    return $token;
  }

}
