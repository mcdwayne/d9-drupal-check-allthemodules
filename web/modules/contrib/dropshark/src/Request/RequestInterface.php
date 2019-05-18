<?php

namespace Drupal\dropshark\Request;

/**
 * Interface RequestInterface.
 */
interface RequestInterface {

  /**
   * Checks to see if a site's token is valid.
   */
  public function checkToken();

  /**
   * Get a token for the site.
   *
   * @param string $email
   *   The user's email.
   * @param string $password
   *   The user's password.
   * @param string $siteId
   *   The site ID.
   *
   * @return string|null
   *   The token provisioned for the site, or NULL if unable to provision a
   *   token.
   */
  public function getToken($email, $password, $siteId);

  /**
   * Posts collected data to the backend.
   *
   * @param array $data
   *   Data to send to the backend.
   *
   * @return object
   *   The response data.
   */
  public function postData(array $data);

}
