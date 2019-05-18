<?php

namespace Drupal\commerce_atom_payment;

/**
 * Implements PaymentAtomForm class.
 *
 * - this class used for build to payment form.
 */
class AtomEncryption {

  /**
   * Mcrypt_module_open is deprecated So working on new_encript.
   */
  public function signature($str, $key) {
    $signature = hash_hmac("sha512", $str, $key, FALSE);
    return $signature;
  }

  /**
   * AES ecription.
   */
  public function aesEncode($plain_text, $key = 'mota3476') {
    return base64_encode(openssl_encrypt($plain_text, "aes-256-cbc", $key, TRUE, str_repeat(chr(0), 16)));
  }

  /**
   * AES ecription.
   */
  public function aesDecode($base64_text, $key = 'mota3476') {
    return openssl_decrypt(base64_decode($base64_text), "aes-256-cbc", $key, TRUE, str_repeat(chr(0), 16));
  }

}
