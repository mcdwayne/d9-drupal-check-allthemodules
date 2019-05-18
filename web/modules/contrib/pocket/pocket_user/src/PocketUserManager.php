<?php

namespace Drupal\pocket_user;

use Drupal\Core\Database\Connection;
use Drupal\pocket\AccessToken;

class PocketUserManager {

  const TABLE = 'pocket_user';

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * PocketUserManager constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * Get the access token for a specific user.
   *
   * @param string $uid
   *
   * @return \Drupal\pocket\AccessToken|null
   */
  public function getUserAccess(string $uid) {
    try {
      $query = $this->database->query(
        'SELECT token, username FROM {pocket_user} WHERE uid = :uid',
        [
          ':uid' => $uid,
        ]
      );
      if ($query && ($result = $query->fetchAssoc())) {
        return new AccessToken($result['token'], $result['username']);
      }
    }
    catch (\Exception $e) {
      watchdog_exception('pocket', $e);
    }

    return NULL;
  }

  /**
   * Set the access token for a specific user.
   *
   * @param string                     $uid
   * @param \Drupal\pocket\AccessToken $token
   *
   * @return bool
   */
  public function setUserAccess(string $uid, AccessToken $token) {
    try {
      return (bool) $this->database->merge('pocket_user')
        ->key('uid', $uid)
        ->fields(
          [
            'uid'      => $uid,
            'token'    => $token->getToken(),
            'username' => $token->getUsername(),
          ]
        )
        ->execute();
    }
    catch (\Exception $e) {
      watchdog_exception('pocket', $e);
      return FALSE;
    }
  }

  /**
   * Delete a user's token.
   *
   * @param string $uid
   *
   * @return bool
   *   TRUE if a token was deleted.
   */
  public function deleteUserAccess(string $uid): bool {
    return (bool) $this->database->delete('pocket_user')
      ->condition('uid', $uid)
      ->execute();
  }
}
