<?php

namespace Drupal\cakephpass\Password;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Password\PhpassHashedPassword;
use Drupal\Core\Site\Settings;

/**
 * Extend Drupal Core PhpassHashedPassword to support CakePhp hashing types.
 *
 * @see http://book.cakephp.org/2.0/en/core-utility-libraries/security.html#Security::hash
 */
class CakePhPassword extends PhpassHashedPassword {

  /**
   * {@inheritdoc}
   */
  public function check($password, $hash) {
    $check = parent::check($password, $hash);

    // If the parent check didn't worked out, try CakePhp hashing types
    // if it's the case.
    if (!$check && substr($hash, 0, 3) == '$C$') {
      $settings = Settings::get('cakephpass', []);
      if (!empty($settings['enabled'])) {
        $check = $this->cakephpHash($password, $hash, $settings);
      }
    }

    return $check;
  }

  /**
   * Check the password against CakePhp hashing.
   *
   * @param string $password
   *   Password value.
   * @param string $hash
   *   Hash value.
   * @param array $settings
   *   Configurations("cakephpass") from settings.php.
   *
   * @return bool
   *   TRUE if the password is valid, FALSE if not.
   */
  private function cakephpHash($password, $hash, array $settings) {

    // The "salt" is mandatory data.
    if (empty($settings['salt'])) {
      return FALSE;
    }

    // Default to "sha1".
    if (empty($settings['type'])) {
      $settings['type'] = 'sha1_strict';
    }

    // String to hash.
    $string = $settings['salt'] . $password;
    // Hash to compute.
    $computedHash = FALSE;
    // Remove CakePHP prefix identifier.
    $hash = ltrim($hash, '$C$');

    switch ($settings['type']) {
      case 'sha1_strict':
        if (function_exists('sha1')) {
          $computedHash = sha1($string);
        }
        break;

      case 'sha256_strict':
        if (function_exists('mhash')) {
          $computedHash = bin2hex(mhash(MHASH_SHA256, $string));
        }
        break;

      default:
        if (function_exists('hash')) {
          $computedHash = hash($settings['type'], $string);
        }
    }

    // If none of above is available, use md5.
    if (!$computedHash) {
      $computedHash = md5($string);
    }

    return $computedHash && Crypt::hashEquals($computedHash, $hash);
  }

}
