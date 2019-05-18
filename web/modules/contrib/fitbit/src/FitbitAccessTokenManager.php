<?php

namespace Drupal\fitbit;

use Drupal\Core\Database\Connection;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;

/**
 * CRUD operations for the fitbit_user_access_tokens table.
 */
class FitbitAccessTokenManager {

  const TOKEN_TABLE = 'fitbit_user_access_tokens';

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Fitbit client.
   *
   * @var \Drupal\fitbit\FitbitClient
   */
  protected $fitbitClient;

  /**
   * FitbitAccessTokenManager constructor.
   *
   * @param Connection $connection
   * @param FitbitClient $fitbit_client
   */
  public function __construct(Connection $connection, FitbitClient $fitbit_client) {
    $this->connection = $connection;
    $this->fitbitClient = $fitbit_client;
  }

  /**
   * Load a single access token.
   *
   * @param int $uid
   *   Drupal user id.
   *
   * @return AccessToken|null
   */
  public function loadAccessToken($uid) {
    $access_tokens = $this->loadMultipleAccessToken([$uid]);
    return isset($access_tokens[$uid]) ? $access_tokens[$uid] : NULL;
  }

  /**
   * Get the access token by Drupal uid. Take care for refreshing
   * the token if necessary.
   *
   * @param int[]|NULL $uids
   *   User id's for which to load access tokens. Pass NULL to load all access
   *   tokens.
   *
   * @return AccessToken[]
   *   Array of access tokens, keyed by uid.
   */
  public function loadMultipleAccessToken($uids = NULL) {
    $access_tokens = [];

    if ($raw_tokens = $this->loadMultiple($uids)) {

      foreach ($raw_tokens as $raw_token) {
        $access_token = new AccessToken([
          'access_token' => $raw_token['access_token'],
          'resource_owner_id' => $raw_token['user_id'],
          'refresh_token' => $raw_token['refresh_token'],
          'expires' => $raw_token['expires'],
        ]);

        try {
          // Check if the access_token is expired. If it is, refresh it and save
          // it to the database.
          if ($access_token->hasExpired()) {
            $access_token = $this->fitbitClient->getAccessToken('refresh_token', ['refresh_token' => $raw_token['refresh_token']]);

            $this->save($raw_token['uid'], [
              'access_token' => $access_token->getToken(),
              'expires' => $access_token->getExpires(),
              'refresh_token' => $access_token->getRefreshToken(),
              'user_id' => $access_token->getResourceOwnerId(),
            ]);
          }

          $access_tokens[$raw_token['uid']] = $access_token;
        }
        catch (IdentityProviderException $e) {
          watchdog_exception('fitbit', $e);
        }
      }
    }

    return $access_tokens;
  }

  /**
   * Load an access token by uid.
   *
   * @param int $uid
   *   User id for which to look up an access token.
   * @return array|null
   *   Returns an associative array of the access token details for the given
   *   uid if they exist, otherwise NULL.
   */
  public function load($uid) {
    $raw_tokens = $this->loadMultiple([$uid]);
    return isset($raw_tokens[$uid]) ? $raw_tokens[$uid] : NULL;
  }

  /**
   * Loads one or more access tokens.
   *
   * @param array|NULL $uids
   *  An array of uids, or NULL to load all access tokens.
   */
  public function loadMultiple($uids = NULL) {
    $query = $this->connection->select(self::TOKEN_TABLE, 'f')
      ->fields('f');
    if (!empty($uids)) {
      $query->condition('uid', $uids, 'IN');
    }
    return $query->execute()
      ->fetchAllAssoc('uid', \PDO::FETCH_ASSOC);
  }

  /**
   * Save access token details for the given uid.
   *
   * @param int $uid
   *   User id for which to save access token details.
   * @param array $data
   *   Associative array of access token details.
   */
  public function save($uid, $data) {
    $this->connection->merge(self::TOKEN_TABLE)
      ->key(['uid' => $uid])
      ->fields($data)
      ->execute();
  }

  /**
   * Delete access token details for the given uid.
   *
   * @param int $uid
   *   User id for which to delete access token details.
   */
  public function delete($uid) {
    $this->connection->delete(self::TOKEN_TABLE)
      ->condition('uid', $uid)
      ->execute();
  }
}
