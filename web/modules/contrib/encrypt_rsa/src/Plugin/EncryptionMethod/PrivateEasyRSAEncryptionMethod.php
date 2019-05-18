<?php

namespace Drupal\encrypt_rsa\Plugin\EncryptionMethod;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\encrypt\Plugin\EncryptionMethod\EncryptionMethodBase;
use ParagonIE\EasyRSA\EasyRSA;
use ParagonIE\EasyRSA\PrivateKey;

/**
 * Provide a private key based EasyRSA encryption method.
 *
 * This encryption method use phpseclib wrapper "EasyRSA" to encrypt/decrypt
 * using a private key.
 *
 * @see https://github.com/paragonie/EasyRSA
 *
 * @EncryptionMethod(
 *   id = "private_easyrsa",
 *   title = @Translation("Private EasyRSA"),
 *   description = @Translation("Encrypt/Decrypt data using EasyRSA wrapper of phpseclib."),
 *   key_type = {"pem_private"}
 * )
 */
class PrivateEasyRSAEncryptionMethod extends EncryptionMethodBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function encrypt($text, $key) {
    $privateKey = new PrivateKey($key);
    $cyphertext = EasyRSA::encrypt($text, $privateKey->getPublicKey());
    return base64_encode($cyphertext);
  }

  /**
   * {@inheritdoc}
   */
  public function decrypt($text, $key) {
    $privateKey = new PrivateKey($key);
    return EasyRSA::decrypt(base64_decode($text), $privateKey);
  }

  /**
   * {@inheritdoc}
   */
  public function checkDependencies($text = NULL, $key = NULL) {
    $errors = [];

    if (!class_exists('\ParagonIE\EasyRSA\EasyRSA')) {
      $errors[] = $this->t('EasyRSA wrapper is not correctly installed.');
    }

    if (!class_exists('\Defuse\Crypto\Crypto')) {
      $errors[] = $this->t('Defuse PHP Encryption library is not correctly installed.');
    }

    if (!class_exists('\phpseclib\Crypt\RSA')) {
      $errors[] = $this->t('Phpseclib encryption library is not correctly installed.');
    }

    return $errors;
  }

}
