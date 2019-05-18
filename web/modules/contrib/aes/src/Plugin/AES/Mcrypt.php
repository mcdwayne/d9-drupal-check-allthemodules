<?php

/**
 * Mcrypt plugin facade.
 */

namespace Drupal\aes\Plugin\AES;

use Drupal\aes\Plugin\AESPluginBase;
use Drupal\Core\Config\FileStorageFactory;

/**
 * Mcrypt plugin implementation
 *
 * How-to use it
 * $test = \Drupal::service('plugin.manager.aes')->getInstanceById('aes_mcrypt')->encrypt('xxx');
 * \Drupal::service('plugin.manager.aes')->getInstanceById('aes_mcrypt')->decrypt($test) === 'xxx';
 *
 * @Cryptor(
 *   id = "aes_mcrypt",
 *   label = "AES mcrypt",
 *   description = "Mcrypt AES encryption plugin.",
 * )
 *
 * @package Drupal\aes\Plugin\AES
 */
class Mcrypt extends AESPluginBase {

  /**
   * Constructor.
   */
  public function __construct() {
    parent::__construct([], 'aes_mcrypt', []);
  }

  /**
   * Reverse the string.
   *
   * {@inheritdoc}
   */
  public function encrypt($data, $key = FALSE, $cipher = FALSE) {
    $config = FileStorageFactory::getActive()->read('aes.settings');
    $iv = base64_decode($config['mcrypt_iv']);
    if (!$key) {
      $key = $config['key'];
    }
    if (!$cipher) {
      $cipher = $config['cipher'];
    }
    $td = mcrypt_module_open($cipher, '', MCRYPT_MODE_CBC, '');

    if (empty($iv)) {
      self::make_iv();
      $config = FileStorageFactory::getActive()->read('aes.settings');
      $iv = base64_decode($config['mcrypt_iv']);
      \Drupal::logger('aes')
        ->warning(
          'No initialization vector found while trying to encrypt! Recreated a new one now and will try to carry on as normal.'
        );
    }

    $ks = mcrypt_enc_get_key_size($td);
    $key = substr(sha1($key), 0, $ks);

    mcrypt_generic_init($td, $key, $iv);
    $encrypted = mcrypt_generic($td, $data);
    mcrypt_generic_deinit($td);
    mcrypt_module_close($td);

    return $encrypted;
  }

  /**
   * Recover previously scrambled string.
   *
   * {@inheritdoc}
   */
  public function decrypt($data, $key = FALSE, $cipher = FALSE) {
    $config = FileStorageFactory::getActive()->read('aes.settings');
    $iv = base64_decode($config['mcrypt_iv']);
    if (!$key) {
      $key = $config['key'];
    }
    if (!$cipher) {
      $cipher = $config['cipher'];
    }
    $td = mcrypt_module_open($cipher, '', MCRYPT_MODE_CBC, '');

    $ks = mcrypt_enc_get_key_size($td);

    if (empty($iv)) {
      \Drupal::logger('aes')->error('No initialization vector found while trying to decrypt with mcrypt. Aborting!');

      return FALSE;
    }

    $key = substr(sha1($key), 0, $ks);
    mcrypt_generic_init($td, $key, $iv);
    $decrypted = mdecrypt_generic($td, $data);
    mcrypt_generic_deinit($td);
    mcrypt_module_close($td);

    return trim($decrypted);
  }
}

