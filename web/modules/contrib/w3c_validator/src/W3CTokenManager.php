<?php

namespace Drupal\w3c_validator;

use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\Entity\User;

/**
 * Token manager: allow to be logged as a user from URL.
 */
class W3CTokenManager {

  /**
   * The current database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a W3CTokenManager object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The active database connection.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(Connection $database, AccountInterface $current_user) {
    $this->database = $database;
    $this->currentUser = $current_user;
  }

  /**
   * Create and store a token to allow access as per specified user. If not
   * specified, then the current user is used.
   */
  function createAccessToken($user = NULL) {

    // Get current user if no custom value.
    if (!isset($user)) {
      $user = $this->currentUser;
    }

    // Build unique token.
    $time = time() + 20;
    $rand = mt_rand();
    $token = md5('w3c_validator' . $time . $rand . $user->id());

    // Store unique token.
    $this->database->insert('w3c_access_token')
      ->fields([
        'token'       => $token,
        'expiration'  => $time,
        'rand'        => $rand,
        'uid'         => $user->id(),
      ])
      ->execute();

    return $token;
  }

  /**
   * Rewoke and access token.
   *
   * @param string $token
   *   The token to rewoke.
   */
  function rewokeAccessToken($token) {
    if ($token != NULL) {
      $this->database->delete('w3c_access_token')
      ->condition('token', $token)
      ->execute();
    }
  }


  /**
   * Retrieve a user from a token.
   *
   * @param string $token
   *   The access token to check.
   *
   * @return \Drupal\user\Entity\User|null
   *   An access token or null if not existing or expired.
   */
  function getUserFromToken($token) {

    $result = $this->database->select('w3c_access_token', 't')
      ->fields('t')
      ->condition('token', $token)
      ->execute()
      ->fetchObject();

    return User::load($result->uid);
  }
}
