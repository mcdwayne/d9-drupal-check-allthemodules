<?php

namespace Drupal\encrypt_rsa\Plugin\EncryptionMethod;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\encrypt\Plugin\EncryptionMethod\EncryptionMethodBase;
use ParagonIE\EasyRSA\EasyRSA;
use ParagonIE\EasyRSA\PublicKey;

/**
 * Provide a public key based EasyRSA encryption method.
 *
 * This encryption method use phpseclib wrapper "EasyRSA" to only encrypt using
 * a public key.
 *
 * @see https://github.com/paragonie/EasyRSA
 *
 * @EncryptionMethod(
 *   id = "public_easyrsa",
 *   title = @Translation("Public EasyRSA"),
 *   description = @Translation("Encrypt(-only) data using EasyRSA wrapper of phpseclib."),
 *   key_type = {"pem_public"}
 * )
 */
class PublicEasyRSAEncryptionMethod extends EncryptionMethodBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function encrypt($text, $key) {
    $cyphertext = EasyRSA::encrypt($text, new PublicKey($key));
    return base64_encode($cyphertext);
  }

  /**
   * {@inheritdoc}
   */
  public function decrypt($text, $key) {
    // Decrypting with a public key is not possible.
    return $text;
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
