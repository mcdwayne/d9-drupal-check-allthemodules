<?php

namespace Drupal\analytics_auth0;

use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Database\Connection;

/**
 * Controller for Drupal user and Auth0 user.
 */
class AnalyticsAuth0Manager {

  /**
   * Current user.
   *
   * @var Drupal\Core\Session\AccountProxy
   */
  protected $user;

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs the AnalyticsAuth0Manager.
   *
   * @param Drupal\Core\Session\AccountProxy $account
   *   Current account.
   * @param \Drupal\Core\Database\Connection $connection
   *   Established database connection.
   */
  public function __construct(AccountProxy $account, Connection $connection) {
    $this->user = $account;
    $this->connection = $connection;
  }

  /**
   * Get the auth0 user ID to pass in with our Analytics.
   *
   * @param int $drupalId
   *   The Drupal user account ID.
   *
   * @return mixed
   *   Auth0 user object.
   */
  private function findAuth0User($drupalId) {
    $auth0User = $this->connection->select('auth0_user', 'a')
      ->fields('a', ['auth0_id'])
      ->condition('drupal_id', $drupalId, '=')
      ->execute()
      ->fetchAssoc();

    return empty($auth0User) ? FALSE : $auth0User;
  }

  /**
   * Get the auth0 user ID to pass in with our Analytics.
   *
   * @return int
   *   Auth0 user ID.
   */
  public function getAuth0User() {
    $drupalUser = $this->user->id();
    $auth0User = $this->findAuth0User($drupalUser);
    $auth0UserId = $auth0User['auth0_id'];

    return $auth0UserId;
  }

}
