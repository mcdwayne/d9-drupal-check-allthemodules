<?php

namespace Drupal\sms_mailup;

/**
 * The MailUp service interface.
 */
interface MailUpServiceInterface {

  /**
   * Get account details.
   *
   * @param string $gateway_id
   *   A gateway entity ID.
   *
   * @return array|FALSE
   *   An array of details, or FALSE if there is a problem with authentication.
   *
   * @throws \Exception
   *   If request fails.
   */
  public function getDetails($gateway_id);

  /**
   * Get the secret for a list using transactional API.
   *
   * Caches the secret in Drupal state.
   *
   * @param string $username
   *   A Mailup account user name.
   * @param string $password
   *   A Mailup account password
   * @param string $guid
   *   A Mailup list GUID.
   *
   * @return string|NULL
   *   The list secret, or NULL if there was a failure.
   */
  function getListSecret($username, $password, $guid);

}
