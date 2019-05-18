<?php
/**
 * @file
 * Contains Drupal\encrypt_seclib\Plugin\EncryptionMethod\PHPSecLibEncryption.
 */

namespace Drupal\encrypt_seclib\Plugin\EncryptionMethod;

use Drupal\encrypt\EncryptionMethodInterface;
use Drupal\encrypt\Plugin\EncryptionMethod\EncryptionMethodBase;
use phpseclib\Crypt\AES;

/**
 * PHPSecLibEncryption class.
 *
 * @EncryptionMethod(
 *   id = "phpseclib",
 *   title = @Translation("PHP Secure Communications Library (phpseclib)"),
 *   description = "Uses the <a href='http://phpseclib.sourceforge.net/'>phpseclib</a> library. This method is only preferable if you cannot install mcrypt.",
 *   key_type = {"encryption"}
 * )
 */
class PHPSecLibEncryption extends EncryptionMethodBase implements EncryptionMethodInterface {

  /**
   * {@inheritdoc}
   */
  public function encrypt($text, $key) {
    $aes = new AES();
    $aes->setKey($key);
    $processed_text = $aes->encrypt($text);
    return $processed_text;
  }

  /**
   * @return mixed
   */
  public function decrypt($text, $key) {
    $aes = new AES();
    $aes->setKey($key);
    $processed_text = $aes->decrypt($text);
    return $processed_text;
  }

  /**
   * @return mixed
   */
  public function checkDependencies($text = NULL, $key = NULL) {
    $errors = [];
    // Check for PHPSecLib class.
    if (!class_exists('phpseclib\Crypt\AES')) {
      $errors[] = 'PHPSecLib is missing. Please ensure proper installation with Composer Manager.';
    }
    return $errors;
  }

}
