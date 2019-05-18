<?php

namespace Drupal\commerce_cib;

interface EncryptionInterface {

  /**
   * Sets the DES keyfile.
   *
   * @param string $path
   *   The file path to the keyfile.
   */
  public function setKeyfile($path);

  /**
   * Sets the DES key.
   *
   * Usually called only through setKeyfile().
   *
   * @param string $key
   *   The DES key.
   */
  public function setKey($key);

  /**
   * Sets the DES initialization vector.
   *
   * Usually called only through setKeyfile().
   *
   * @param string $iv
   *   The DES initialization vector.
   */
  public function setIv($iv);

  /**
   * Gets the DES key.
   *
   * @return string
   *   The DES key.
   */
  public function getKey();

  /**
   * Gets the DES initialization vector.
   *
   * @return string
   *   The DES initialization vector.
   */
  public function getIv();

  /**
   * Encrypt a url encoded message using 3DES-EDE-CBC for sending to CIB.
   *
   * @param string $url_encoded_message
   *   The url encoded string to encode.
   *
   * @return string
   *   The encrypted string.
   */
  public function encrypt($url_encoded_message);

  /**
   * Decrypt a 3DES-EDE-CBC encrypted message.
   *
   * @param string $encrypted_message
   *   The encrypted message.
   *
   * @return string
   *   The decrypted string.
   */
  public function decrypt($encrypted_message);

}
