<?php

namespace Drupal\i18n_sso\Service;

use Drupal\Core\Database\Connection;

/**
 * Class Token.
 */
class Token {

  /**
   * The table used to store the tokens.
   */
  const TOKEN_TABLE = 'i18n_sso_tokens';

  /**
   * The token lifetime.
   */
  const TOKEN_LIFETIME = 60 * 10;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection object.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
    return $this;
  }

  /**
   * Returns a valid token stored in database for given IP and uid.
   *
   * @param string $user_ip
   *   The IP of the user.
   * @param int $uid
   *   The user ID.
   *
   * @return object
   *   A stdclass object containing a token field.
   */
  public function getToken($user_ip, $uid) {
    /** @var \Drupal\Core\Database\Query\SelectInterface $token */
    $token = $this->connection->select(self::TOKEN_TABLE)
      ->fields(self::TOKEN_TABLE, ['token'])
      ->condition('user_ip', $user_ip, 'LIKE')
      ->condition('uid', $uid, '=')
      ->condition('expire', $_SERVER['REQUEST_TIME'], '>');
    return $token->range(0, 1)->execute()->fetchObject();
  }

  /**
   * Creates a token (insert into database).
   *
   * Returns the result of getToken with given parameters.
   *
   * @param string $user_ip
   *   The IP of the user.
   * @param int $uid
   *   The user ID.
   *
   * @return object
   *   A stdclass object containing a token field.
   */
  public function createToken($user_ip, $uid) {
    $token = [];
    $token['uid'] = $uid;
    $token['created'] = $_SERVER['REQUEST_TIME'];
    $sha1source = $token['created'] . $uid . $user_ip;
    $token['token'] = sha1($sha1source);
    $token['user_ip'] = $user_ip;
    $token['expire'] = $_SERVER['REQUEST_TIME'] + self::TOKEN_LIFETIME;
    $this->connection
      ->insert(self::TOKEN_TABLE)
      ->fields($token)
      ->execute();
    return $this->getToken($user_ip, $uid);
  }

  /**
   * Returns user_id from token tables given a token and an IP address.
   *
   * @param string $user_ip
   *   The IP of the user.
   * @param string $token
   *   The token used to authenticate the user.
   *
   * @return mixed
   *   The user ID if it is found with an non expired token.
   */
  public function getUserId($user_ip, $token) {
    /** @var \Drupal\Core\Database\Query\SelectInterface $query */
    $query = $this->connection->select(self::TOKEN_TABLE)
      ->fields(self::TOKEN_TABLE, ['uid'])
      ->condition('user_ip', $user_ip, 'LIKE')
      ->condition('token', $token, 'LIKE')
      ->condition('expire', $_SERVER['REQUEST_TIME'], '>');
    return $query->range(0, 1)->execute()->fetchField();
  }

  /**
   * Deletes the token after it has been used.
   *
   * @param string $user_ip
   *   The IP of the user.
   * @param string $token
   *   The token used to authenticate the user.
   *
   * @return int
   *   The number of row affected.
   */
  public function deleteToken($user_ip, $token) {
    return $this->connection->delete(self::TOKEN_TABLE)
      ->condition('user_ip', $user_ip, 'LIKE')
      ->condition('token', $token, 'LIKE')
      ->execute();
  }

  /**
   * Deletes all tokens for a user.
   *
   * @param int $uid
   *   The user ID.
   *
   * @return int
   *   The number of row affected.
   */
  public function deleteUserTokens($uid) {
    return $this->connection->delete(self::TOKEN_TABLE)
      ->condition('uid', $uid, '=')
      ->execute();
  }

  /**
   * Deletes all expired tokens.
   *
   * @return int
   *   The number of row affected.
   */
  public function deleteExpiredTokens() {
    return $this->connection->delete(self::TOKEN_TABLE)
      ->condition('expire', $_SERVER['REQUEST_TIME'], '<')
      ->execute();
  }

}
