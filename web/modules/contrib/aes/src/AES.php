<?php
/**
 * Core Encryption class.
 */

namespace Drupal\aes;

use Drupal\Core\Config\FileStorageFactory;

class AES {
  /**
   * Retrieve information about available AES implementations.
   *
   * @return array
   */
  static public function get_available_implementations() {
    $phpsec_available = FALSE;
    if (\Drupal::moduleHandler()->moduleExists('libraries') && libraries_get_path('phpseclib')) {
      $phpsec_include_path = libraries_get_path('phpseclib');
      set_include_path(get_include_path() . PATH_SEPARATOR . $phpsec_include_path);
      $phpsec_available = is_readable($phpsec_include_path . '/Crypt/AES.php');
    }
    $mcrypt_available = extension_loaded('mcrypt');
    return array('mcrypt' => $mcrypt_available, 'phpseclib' => $phpsec_available);
  }

  /**
   * Load PHPSecLib files.
   *
   * @param bool $display_errors
   *   In case of problem with loading library, display errors and warnings.
   *
   * @return bool loading result
   */
  static public function load_phpsec($display_errors = TRUE) {
    $library_error = FALSE;
    if (!\Drupal::moduleHandler()->moduleExists('libraries')) {
      $library_error = t('The Libraries module should be enabled to use phpseclib.');
    }
    elseif (($phpsec_include_path = libraries_get_path('phpseclib')) == FALSE) {
      $library_error = t('The phpseclib package should be installed as a library.');
    }
    elseif (!file_exists($phpsec_include_path . '/Crypt/AES.php')) {
      $library_error = t('Cannot load /Crypt/AES.php from phpseclib root.');
    }
    elseif (!is_readable($phpsec_include_path . '/Crypt/AES.php')) {
      $library_error = t("It appears that phpseclib is installed in the right location but can't be read. Check that the permissions on the directory and its files allows for reading by the webserver.");
    }
    elseif (!function_exists('set_include_path')) {
      $library_error = t('The set_include_path function is inaccessible.');
    }

    if ($library_error) {
      if ($display_errors) {
        drupal_set_message($library_error, 'warning');
      }
      return FALSE;
    }

    // Include phpsec AES lib.
    set_include_path(get_include_path() . PATH_SEPARATOR . $phpsec_include_path);
    include_once('Crypt/AES.php');
    if (class_exists('Crypt_AES')) {
      return TRUE;
    }

    if ($display_errors) {
      drupal_set_message('Including library error', 'error');
    }
    return FALSE;
  }

  /**
   * Retrieve encryption key. Note we're using YAML files not DB settings.
   *
   * @return string encryption key.
   */
  static public function get_key() {
    $config = FileStorageFactory::getActive()->read('aes.settings');
    return isset($config['key']) ? $config['key'] : FALSE;
  }

  /**
   * Generate a random key, containing uppercase, lowercase and digits.
   *
   * @return string encryption key.
   */
  static public function make_key() {
    $keylen = 32;
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
    while (TRUE) {
      $key = '';
      while (strlen($key) < $keylen) {
        $key .= substr($chars, rand(0, strlen($chars)), 1);
      }
      // is there at least one lowercase letter?
      if (!preg_match('/.*[a-z].*/', $key)) {
        continue;
      }
      // is there at least one uppercase letter?
      if (!preg_match('/.*[A-Z].*/', $key)) {
        continue;
      }
      // is there at least one numeric?
      if (!preg_match('/.*[0-9].*/', $key)) {
        continue;
      }
      break;
    }
    \Drupal::logger('aes')->notice('Generated new AES key: ' . substr($key, 0, 4) . str_repeat('*', $keylen - 8) . substr($key, $keylen - 4, 4));
    return $key;
  }

  /**
   * Generate an IV - initialization vector - and store it in configuration.
   *
   * @param bool $ignore_implementation
   */
  static public function make_iv($ignore_implementation = FALSE) {
    $config = FileStorageFactory::getActive()->read('aes.settings');

    // Bail out if using phpseclib
    if ($config['implementation'] == 'phpseclib' && $ignore_implementation == FALSE) {
      \Drupal::logger('aes')->warning("Called make_iv when using phpseclib. This is harmless, but shouldn't happen.");
      return;
    }

    $randgen = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? MCRYPT_RAND : MCRYPT_DEV_URANDOM;
    $cryptor = mcrypt_module_open($config['cipher'], '', MCRYPT_MODE_CBC, '');
    if (!$cryptor) {
      \Drupal::logger('aes')->warning(t('Problem while calling mcrypt_module_open for cipher %cipher.'), array('%cipher' => $config['cipher']));
      return;
    }
    $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($cryptor), $randgen);
    mcrypt_module_close($cryptor);
    $config['mcrypt_iv'] = base64_encode($iv);
    FileStorageFactory::getActive()->write('aes.settings', $config);
  }

  /**
   * Encrypts a string.
   *
   * @param string $string
   *   The string to encrypt.
   * @param bool $base64encode
   *   Whether to return the string base64 encoded (recommended for database insertion).
   * @param string $custom_key
   *   Use this as the key rather than the stored one for this operation.
   * @param string $custom_cipher
   *   Use this cipher rather than the default one. (only with Mcrypt - ignored with phpseclib)
   * @param string $custom_iv
   *   Use this initialization vector instead of the default one.
   * @param string $force_implementation
   *   Can be 'phpseclib', 'mcrypt' or classname of custom implementation. Warning: Does not check if the requested implementation actually exists.
   *
   * @return bool|string
   *   The encrypted string on success, false on error.
   */
  static public function encrypt($string, $base64encode = TRUE, $custom_key = NULL, $custom_cipher = NULL, $custom_iv = NULL, $force_implementation = NULL) {
    // Bail out if the passed string is empty.
    if (empty($string)) {
      \Drupal::logger('aes')->warning('Tried to encrypt an empty string.');
      return FALSE;
    }

    $config = FileStorageFactory::getActive()->read('aes.settings');
    $cipher = empty($custom_cipher) ? $config['cipher'] : $custom_cipher;
    $key = empty($custom_key) ? self::get_key() : $custom_key;
    $implementation = $force_implementation ? $force_implementation : $config['implementation'];

    if ($implementation == 'phpseclib') {
      // The phpseclib doesn't support custom ciphers and iv's.
      if (!empty($custom_cipher)) {
        \Drupal::logger('aes')->warning("A custom cipher was defined when encrypting a string in the AES module using the phpseclib implementation. This implementation doesn't support custom ciphers therefore the argument was ignored and the encryption was done with the standard cipher.");
      }
      if (!empty($custom_iv)) {
        \Drupal::logger('aes')->warning("A custom IV was defined when encrypting a string in the AES module using the phpseclib implementation. This implementation doesn't support custom IV's therefore the argument was ignored and the encryption was done with the standard IV.");
      }

      if (!self::load_phpsec()) {
        return FALSE;
      }
      $phpsec = new \Crypt_AES();
      $phpsec->setKey($key);
      $encrypted = $phpsec->encrypt($string);
      return $base64encode ? base64_encode($encrypted) : $encrypted;
    }
    if ($implementation == 'mcrypt') {
      // @todo remove this because we have Mcrypt plugin.
      $td = mcrypt_module_open($cipher, '', MCRYPT_MODE_CBC, '');
      $iv = base64_decode($custom_iv ? $custom_iv : $config['mcrypt_iv']);

      if (empty($iv)) {
        self::make_iv();
        $config = FileStorageFactory::getActive()->read('aes.settings');
        $iv = base64_decode($config['mcrypt_iv']);
        \Drupal::logger('aes')
          ->warning('No initialization vector found while trying to encrypt! Recreated a new one now and will try to carry on as normal.');
      }

      $ks = mcrypt_enc_get_key_size($td);
      $key = substr(sha1($key), 0, $ks);

      mcrypt_generic_init($td, $key, $iv);
      $encrypted = mcrypt_generic($td, $string);
      mcrypt_generic_deinit($td);
      mcrypt_module_close($td);
      return $base64encode ? base64_encode($encrypted) : $encrypted;
    }

    /* @var \Drupal\aes\Plugin\AESPluginManager $plugin_manager */
    $plugin_manager = \Drupal::service('plugin.manager.aes');
    try {
      /* @var \Drupal\aes\Plugin\AESPluginBase $custom */
      $custom = $plugin_manager->getInstanceById($implementation);
      $encrypted = $custom->encrypt($string, $key, $cipher);
    }
    catch (\Exception $e) {
      $error_msg = t('AES having problems with custom plugin implementation: %plugin . Message: %msg',
        array('%plugin' => $implementation, '%msg' => $e->getMessage()));
      \Drupal::logger('aes')->error($error_msg);
      return FALSE;
    }
    return $base64encode ? base64_encode($encrypted) : $encrypted;
  }

  /**
   * Decrypts a string of encrypted data.
   *
   * @param string $string
   *   The string to decrypt.
   * @param bool $base64encoded
   *   Whether this encrypted string is base64 encoded or not.
   * @param string $custom_key
   *   Use this as the key rather than the stored one for this operation.
   * @param string $custom_cipher
   *   Use this cipher rather than the default one. (only with Mcrypt - ignored with phpseclib)
   * @param string $custom_iv
   *   Use this initialization vector instead of the default one.
   * @param string $force_implementation
   *   Can be 'phpseclib', 'mcrypt' or classname of custom implementation. Warning: Does not check if the requested implementation actually exists.
   *
   * @return bool|string
   *   The decrypted string on success, false on error.
   */
  static public function decrypt($string, $base64encoded = TRUE, $custom_key = NULL, $custom_cipher = NULL, $custom_iv = NULL, $force_implementation = NULL) {
    // Bail out if the passed string is empty.
    if (empty($string)) {
      \Drupal::logger('aes')->warning('Tried to decrypt an empty string.');
      return FALSE;
    }

    $config = FileStorageFactory::getActive()->read('aes.settings');
    if ($base64encoded) {
      $string = base64_decode($string);
    }

    $cipher = empty($custom_cipher) ? $config['cipher'] : $custom_cipher;
    $key = empty($custom_key) ? self::get_key() : $custom_key;
    $implementation = $force_implementation ? $force_implementation : $config['implementation'];

    if ($implementation == 'phpseclib') {
      // The phpseclib doesn't support custom ciphers and iv's.
      if (!empty($custom_cipher)) {
        \Drupal::logger('aes')->warning("A custom cipher was defined when decrypting a string in the AES module using the phpseclib implementation. This implementation doesn't support custom ciphers therefore the argument was ignored and the decryption was done with the standard cipher.");
      }
      if (!empty($custom_iv)) {
        \Drupal::logger('aes')->warning("A custom IV was defined when decrypting a string in the AES module using the phpseclib implementation. This implementation doesn't support custom IV's therefore the argument was ignored and the decryption was done with the standard IV.");
      }

      if (!self::load_phpsec()) {
        return FALSE;
      }
      $phpsec = new \Crypt_AES();
      $phpsec->setKey($key);
      $decrypted = $phpsec->decrypt($string);
      return trim($decrypted);
    }

    if ($implementation == 'mcrypt') {
      // @todo remove this because we have Mcrypt plugin.
      $td = mcrypt_module_open($cipher, '', MCRYPT_MODE_CBC, '');
      $ks = mcrypt_enc_get_key_size($td);
      $iv = base64_decode($custom_iv ? $custom_iv : $config['mcrypt_iv']);
      if (empty($iv)) {
        \Drupal::logger('aes')->error('No initialization vector found while trying to decrypt with mcrypt. Aborting!');
        return FALSE;
      }

      $key = substr(sha1($key), 0, $ks);
      mcrypt_generic_init($td, $key, $iv);
      $decrypted = mdecrypt_generic($td, $string);
      mcrypt_generic_deinit($td);
      mcrypt_module_close($td);
      return trim($decrypted);
    }

    /* @var \Drupal\aes\Plugin\AESPluginManager $plugin_manager */
    $plugin_manager = \Drupal::service('plugin.manager.aes');
    try {
      /* @var \Drupal\aes\Plugin\AESPluginBase $custom */
      $custom = $plugin_manager->getInstanceById($implementation);
      $decrypted = $custom->decrypt($string, $key, $cipher);
    }
    catch (\Exception $e) {
      $error_msg = t('AES having problems with custom plugin implementation: %plugin . Message: %msg',
        array('%plugin' => $implementation, '%msg' => $e->getMessage()));
      \Drupal::logger('aes')->error($error_msg);
      return FALSE;
    }
    return $decrypted;
  }

}
