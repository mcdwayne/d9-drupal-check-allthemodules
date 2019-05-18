<?php

namespace Drupal\encrypt_rsa\Plugin\EncryptionMethod;

use Drupal\encrypt\Exception\EncryptException;
use Drupal\encrypt\Plugin\EncryptionMethod\EncryptionMethodBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provide a public key based OpenSSL Seal encryption method.
 *
 * This encryption method use openssl_seal() to (ONLY) encrypt data from a RSA
 * public .pem key. The Method will use AES256 in PHP >= 7.0.0 to encrypt the
 * random key, falling back to RC4 (unsafe) for PHP 5.x. The output encrypted
 * envelope is a string separated by ENVELOPE_SEPARATOR and made of the
 * following component, in order:
 *  - Sealed encrypted message, base64 encoded.
 *  - Encrypted random key (AES256 or RC4 key), base64 encoded.
 *  - Random IV vector (PHP 7.x) base64 encoded, or NULL (PHP 5.x).
 *
 * @see http://php.net/manual/en/function.openssl-seal.php
 * @see http://php.net/manual/en/function.openssl-open.php
 *
 * @EncryptionMethod(
 *   id = "public_openssl_seal",
 *   title = @Translation("Public OpenSSL Seal"),
 *   description = @Translation("Encrypt data using OpenSSL Seal method with a Public key"),
 *   key_type = {"pem_public"}
 * )
 */
class PublicOpenSslSealEncryptionMethod extends EncryptionMethodBase {

  const ENVELOPE_SEPARATOR = ',';

  /**
   * Encrypt text.
   *
   * @param string $text
   *   The text to be encrypted.
   * @param string $key
   *   The key to encrypt the text with.
   *
   * @return string
   *   The base64 encrypted message + random key, separated by
   *   ENVELOPE_SEPARATOR.
   */
  public function encrypt($text, $key) {

    $sealed = "";
    $ekv = [];

    $pubKey = openssl_pkey_get_public($key);
    $iv = NULL;
    if (version_compare(PHP_VERSION, '7.0.0', '>=')) {
      $iv = openssl_random_pseudo_bytes(16);
      openssl_seal($text, $sealed, $ekv, [$pubKey], "AES256", $iv);
    }
    else {
      openssl_seal($text, $sealed, $ekv, [$pubKey]);
    }

    openssl_free_key($pubKey);

    return base64_encode($sealed) . static::ENVELOPE_SEPARATOR . base64_encode($ekv[0]) . static::ENVELOPE_SEPARATOR . base64_encode($iv);
  }

  /**
   * {@inheritdoc}
   */
  public function decrypt($text, $key) {
    // You can't decrypt data with a Public key.
    return $text;
  }

  /**
   * {@inheritdoc}
   */
  public function checkDependencies($text = NULL, $key = NULL) {
    $errors = [];

    if (!extension_loaded('openssl')) {
      $errors[] = $this->t('OpenSSL extensions is required for this Encryption Method.');
    }

    return $errors;
  }

}
