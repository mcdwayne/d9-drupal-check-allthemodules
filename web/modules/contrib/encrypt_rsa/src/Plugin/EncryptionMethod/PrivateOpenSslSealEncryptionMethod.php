<?php

namespace Drupal\encrypt_rsa\Plugin\EncryptionMethod;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\encrypt\Exception\EncryptException;
use Drupal\encrypt\Plugin\EncryptionMethod\EncryptionMethodBase;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provide a private key based OpenSSL Seal encryption method.
 *
 * This encryption method use openssl_seal()/openssl_open() to encrypt/decrypt
 * data from a RSA private .pem key. The Method will use AES256 in PHP >= 7.0.0
 * to encrypt the random key, falling back to RC4 (unsafe) for PHP 5.x. The
 * output encrypted envelope is a string separated by ENVELOPE_SEPARATOR and
 * made of the following component, in order:
 *  - Sealed encrypted message, base64 encoded.
 *  - Encrypted random key (AES256 or RC4 key), base64 encoded.
 *  - Random IV vector (PHP 7.x) base64 encoded, or NULL (PHP 5.x).
 *
 * @see http://php.net/manual/en/function.openssl-seal.php
 * @see http://php.net/manual/en/function.openssl-open.php
 *
 * @EncryptionMethod(
 *   id = "private_openssl_seal",
 *   title = @Translation("Private OpenSSL Seal"),
 *   description = @Translation("Encrypt/Decrypt data using OpenSSL Seal method with a Private key"),
 *   key_type = {"pem_private"}
 * )
 */
class PrivateOpenSslSealEncryptionMethod extends EncryptionMethodBase implements ContainerFactoryPluginInterface {

  const ENVELOPE_SEPARATOR = ',';

  /**
   * State API service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

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

    $privKey = openssl_pkey_get_private($key, $this->getPassPhrase($key));
    $pubKey = openssl_pkey_get_public(openssl_pkey_get_details($privKey)['key']);
    $iv = openssl_random_pseudo_bytes(16);
    if (version_compare(PHP_VERSION, '7.0.0', '>=')) {
      openssl_seal($text, $sealed, $ekv, [$pubKey], "AES256", $iv);
    }
    else {
      openssl_seal($text, $sealed, $ekv, [$pubKey]);
    }

    openssl_free_key($privKey);
    openssl_free_key($pubKey);

    return base64_encode($sealed) . static::ENVELOPE_SEPARATOR . base64_encode($ekv[0]) . static::ENVELOPE_SEPARATOR . base64_encode($iv);
  }

  /**
   * {@inheritdoc}
   */
  public function decrypt($text, $key) {
    $envelope = explode(',', $text);

    if (count($envelope) !== 3) {
      return $text;
    }

    $privKey = openssl_pkey_get_private($key, $this->getPassPhrase($key));
    if (version_compare(PHP_VERSION, '7.0.0', '>=')) {
      openssl_open(base64_decode($envelope[0]), $decryptedData, base64_decode($envelope[1]), $privKey, "AES256", base64_decode($envelope[2]));
    } else{
      openssl_open(base64_decode($envelope[0]), $decryptedData, base64_decode($envelope[1]), $privKey);
    }

    openssl_free_key($privKey);
    return $decryptedData;

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

  /**
   * Get private key passphrase.
   *
   * @param string $key_value
   *   The key value, to extract its md5 hash.
   *
   * @return string
   *   Return the key passphrase.
   */
  private function getPassPhrase($key_value) {
    // Currently there is no way to pass options to EncryptionMethod(s), so use
    // the State API to provide a passphrase.
    // TODO: review this code when Issue #2749349 is in.
    // @see https://www.drupal.org/project/encrypt/issues/2749349
    $key_hash = md5($key_value);

    return $this->state->get('encrypt_rsa.private.' . $key_hash . '.passphrase', '');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->state = $container->get('state');
    return $instance;
  }

}
