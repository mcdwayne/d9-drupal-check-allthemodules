<?php

namespace Drupal\auto_login_url;

use \Drupal\Core\Database\Connection;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Site\Settings;

class AutoLoginUrlCreate {

  /**
   * Drupal\Core\Database\Connection definition.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructor.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * Create an auto login hash on demand.
   *
   * @param int $uid
   *   User id.
   * @param string $destination
   *   Destination URL.
   * @param bool $absolute
   *   Absolute or relative link.
   *
   * @return string
   *   Auto Login URL.
   */
  function create($uid, $destination, $absolute = FALSE) {
    $config = \Drupal::config('auto_login_url.settings');

    // Get ALU secret.
    $auto_login_url_secret = \Drupal::service('auto_login_url.general')->getSecret();

    // Get user password.
    $password = \Drupal::service('auto_login_url.general')->getUserHash($uid);

    // Create key.
    $key = Settings::getHashSalt() . $auto_login_url_secret . $password;

    // Repeat until the hash that is saved in DB is unique.
    $hash_helper = 0;

    do {
      $data = $uid . microtime(TRUE). $destination . $hash_helper;

      // Generate hash.
      $hash = Crypt::hmacBase64($data, $key);

      // Get substring.
      $hash = substr($hash, 0, $config->get('token_length'));

      // Generate hash to save to DB.
      $hash_db = Crypt::hmacBase64($hash, $key);

      // Check hash is unique.
      $result = $this->connection->select('auto_login_url', 'alu')
        ->fields('alu', ['hash'])
        ->condition('alu.hash', $hash_db)
        ->execute()
        ->fetchAssoc();

      // Increment value in case there will be a next iteration.
      $hash_helper++;

    } while (isset($result['hash']));

    // Insert a new hash.
    $this->connection->insert('auto_login_url')
      ->fields(['uid', 'hash', 'destination', 'timestamp'])
      ->values([
        'uid' => $uid,
        'hash' => $hash_db,
        'destination' => $destination,
        'timestamp' => time(),
      ])
      ->execute();

    // Check if link is absolute.
    $absolute_path = '';
    if ($absolute) {
      global $base_url;
      $absolute_path = $base_url . '/';
    }

    return $absolute_path . 'autologinurl/' . $uid . '/' . $hash;
  }

  /**
   * Convert a whole text (E.g. mail with autologin links).
   *
   * @param int $uid
   *   User id.
   * @param string $text
   *   Text to change links to.
   *
   * @return string
   *   The text with changed links.
   */
  function convertText($uid, $text) {

    global $base_root;
    // A pattern to convert links, but not images.
    // I am not very sure about that.
    $pattern = '/' . str_replace('/', '\\/', $base_root) . '\\/[^\s^"^\']*/';

    // Create a new object and pass the uid.
    $current_conversion = new AutoLoginUrlConvertTextClass($uid);

    // Replace text with regex/callback.
    $text = preg_replace_callback(
      $pattern,
      [&$current_conversion, 'replace'],
      $text);

    return $text;
  }
}
