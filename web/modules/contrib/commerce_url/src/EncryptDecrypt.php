<?php

namespace Drupal\commerce_url;

/**
 * Encrypt and Decrypt.
 */
class EncryptDecrypt {

  /**
   * Encryption constant.
   */
  const ENCRYPT = "encrypt";

  /**
   * Decryption constant.
   */
  const DECRYPT = "decrypt";

  /**
   * The function to encrypt and decrypt the order number.
   *
   * @param string $string
   *   String data.
   * @param string $action
   *   Action data.
   *
   * @return bool|string
   *   return output.
   */
  public function customEncryptDecrypt($string, $action = self::ENCRYPT) {
    $secret_key = 'customEncryptDecrypt_secret_key';
    $secret_iv = 'customEncryptDecrypt_secret_iv';
    $output = FALSE;
    $encrypt_method = "AES-256-CBC";
    $key = hash('sha256', $secret_key);
    $iv = substr(hash('sha256', $secret_iv), 0, 4);
    if ($action == self::ENCRYPT) {
      $output = base64_encode(openssl_encrypt($string, $encrypt_method, $key, 0, $iv));
    }
    elseif ($action == self::DECRYPT) {
      $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
    }
    return $output;
  }

}
