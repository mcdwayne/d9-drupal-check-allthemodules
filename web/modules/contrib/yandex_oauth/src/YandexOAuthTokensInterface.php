<?php

namespace Drupal\yandex_oauth;

/**
 * Defines an interface for a 'yandex_oauth' service.
 */
interface YandexOAuthTokensInterface {

  /**
   * Saves the access token information record to the database.
   *
   * @param string $name
   *   Yandex account name.
   * @param int $uid
   *   Site's user ID.
   * @param string $token
   *   The access token string.
   * @param int $expire
   *   Token expiration timestamp.
   *
   * @return int|null
   *   If the record insert or update failed, returns NULL. If it succeeded,
   *   returns one of Merge class constants, depending on the operation
   *   performed.
   */
  public function save($name, $uid, $token, $expire);

  /**
   * Given an account name loads the access token information from a database.
   *
   * @param string $name
   *   Yandex account name.
   * @param bool $fresh
   *   (optional) Set to FALSE to load the record even if token has expired.
   *
   * @return object|null
   *   The object with token info if conditions matching token exists. Otherwise
   *   returns NULL. Fields of returning object:
   *   - name: The same account name given in an argument.
   *   - uid: ID of user associated with this Yandex account.
   *   - token: The access token string.
   *   - expire: Token expiration timestamp.
   */
  public function get($name, $fresh = TRUE);

}
