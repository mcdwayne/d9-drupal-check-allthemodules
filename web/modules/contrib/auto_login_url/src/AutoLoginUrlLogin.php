<?php

namespace Drupal\auto_login_url;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Site\Settings;
use Drupal\user\Entity\User;


/**
 * Class AutoLoginUrlLogin.
 *
 * @package Drupal\auto_login_url
 */
class AutoLoginUrlLogin {

  /**
   * Constructor.
   */
  public function __construct() {

  }

  /**
   * Get destination URL for autologin hash.
   *
   * @param integer $uid
   *   User id.
   * @param string $hash
   *   Hash string.
   *
   * @return string|bool
   *   Destination or FALSE
   */
  function login($uid, $hash) {

    $config = \Drupal::config('auto_login_url.settings');
    $connection = \Drupal::database();

    // Get ALU secret.
    $auto_login_url_secret = \Drupal::service('auto_login_url.general')->getSecret();

    // Get user password.
    $password = \Drupal::service('auto_login_url.general')->getUserHash($uid);

    // Create key.
    $key = Settings::getHashSalt() . $auto_login_url_secret . $password;

    // Get if the hash is in the db.
    $result = $connection->select('auto_login_url', 'a')
      ->fields('a', ['id', 'uid', 'destination'])
      ->condition('hash', Crypt::hmacBase64($hash, $key), '=')
      ->execute()
      ->fetchAssoc();

    if (count($result) > 0 && isset($result['uid'])) {
      $account = User::load($result['uid']);
      user_login_finalize($account);

      // Update the user table timestamp noting user has logged in.
      $connection->update('users_field_data')
        ->fields(['login' => time()])
        ->condition('uid', $result['uid'])
        ->execute();

      // Delete auto login URL, if option checked.
      if ($config->get('delete')) {
        $connection->delete('auto_login_url')
          ->condition('id', [$result['id']])
          ->execute();
      }

      // Get destination URL.
      $destination = urldecode($result['destination']);
      $destination =
        strpos($destination, 'http://') !== FALSE
        || strpos($destination, 'https://') !== FALSE ?
          $destination : '/' . $destination;

      // Return destination.
      return $destination;
    }

    return FALSE;
  }

}
