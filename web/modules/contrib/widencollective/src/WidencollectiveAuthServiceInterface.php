<?php

namespace Drupal\widencollective;

/**
 * Interface WidencollectiveAuthServiceInterface.
 *
 * @package Drupal\widencollective
 */
interface WidencollectiveAuthServiceInterface {

  /**
   * Returns widen setting config where it stores the authentication data.
   */
  public static function getConfig();

  /**
   * Gets endpoint or path.
   *
   * @param string $method
   *   The method to be called in the API.
   *
   * @return string
   *   The absolute path of the endpoint of the method.
   */
  public static function getEndpoint($method);

  /**
   * Provides the authorization link with Widen Collective.
   *
   * @param string $return_link
   *   The url where it should redirect after the authentication.
   *
   * @return string
   *   The absolute URL used for authorization.
   */
  public static function generateAuthUrl($return_link);

  /**
   * Purge widen authorization connection.
   *
   * @param string $access_token
   *   Widen user token.
   *
   * @return bool
   *   Returns a boolean based on authorization.
   */
  public static function cancel($access_token);

  /**
   * Authenticates the user.
   *
   * @param string $auth_code
   *   The authorization code.
   *
   * @return array
   *   The response data of the authentication attempt.
   */
  public static function authenticate($auth_code);

}
