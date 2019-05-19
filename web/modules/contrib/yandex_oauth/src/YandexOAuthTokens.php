<?php

namespace Drupal\yandex_oauth;

use Drupal\Core\Database\Connection;

/**
 * Provides YandexOAuthTokens, a 'yandex_oauth' service class.
 */
class YandexOAuthTokens implements YandexOAuthTokensInterface {

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Static cache of DB select results.
   *
   * @var object[]
   */
  protected static $cache = [];

  /**
   * Constructs a new YandexOAuthTokens.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database service.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public function save($name, $uid, $token, $expire) {
    $fields = ['uid' => $uid, 'token' => $token, 'expire' => $expire];
    return $this->database->merge('yandex_oauth')
      ->fields($fields)
      ->key('name', $name)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function get($name, $fresh = TRUE) {
    if (!isset(self::$cache[$name])) {
      self::$cache[$name] = $this->database->select('yandex_oauth', 'yo')
        ->fields('yo')
        ->condition('name', $name)
        ->execute()
        ->fetchObject();
    }

    if (self::$cache[$name] && (self::$cache[$name]->expire > REQUEST_TIME || !$fresh)) {
      return self::$cache[$name];
    }
  }

}
