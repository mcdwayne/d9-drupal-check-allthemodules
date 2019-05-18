<?php

namespace Drupal\auto_login_url;

use Drupal\Component\Utility\Random;
use Drupal\Core\Config\ConfigFactory;


/**
 * Class AutoLoginUrlGeneral.
 *
 * @package Drupal\auto_login_url
 */
class AutoLoginUrlGeneral {

  /**
   * Constructor.
   */
  public function __construct() {

  }

  /**
   * Check if this IP is blocked by flood.
   *
   * @return bool
   *   TRUE if it is blocked.
   */
  function checkFlood() {
    // Maybe use DI in the future.
    $flood_config = \Drupal::config('user.flood');
    $flood = \Drupal::flood();

    if (!$flood->isAllowed('user.failed_login_ip', $flood_config->get('ip_limit'), $flood_config->get('ip_window'))) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Register flood event for this IP.
   *
   * @param string $hash
   *   Code that passes through URL.
   */
  function registerFlood($hash) {

    $flood_config = \Drupal::config('user.flood');
    $flood = \Drupal::flood();

    // Register flood event.
    $flood->register('user.failed_login_ip', $flood_config->get('ip_window'));

    // Log error.
    \Drupal::logger('auto_login_url')
      ->error('Failed Auto Login URL from ip: @ip and hash: @hash',
        [
          '@ip' => \Drupal::request()->getClientIp(),
          '@hash' => $hash
        ]);
  }

  /**
   * Get secret key for ALU or create now.
   */
  function getSecret() {

    $config = \Drupal::config('auto_login_url.settings');

    // Check if it exists.
    $secret = $config->get('secret');

    // Create if it does not exist.
    if ($secret == '') {
      $random_generator = new Random();
      $secret = $random_generator->name(64);

      \Drupal::configFactory()->getEditable('auto_login_url.settings')
        ->set('secret', $secret)->save();
    }

    return $secret;
  }

  /**
   * Get user password hash.
   *
   * @param integer $uid
   *   User id.
   *
   * @return string
   *   Hashed password.
   */
  function getUserHash($uid) {
    $query = \Drupal::database()->select('users_field_data', 'u');
    $query->addField('u', 'pass');
    $query->condition('u.uid', $uid);
    $query->range(0, 1);

    return $query->execute()->fetchField();
  }

}
