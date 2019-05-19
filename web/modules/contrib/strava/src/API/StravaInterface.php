<?php

namespace Drupal\strava\Api;

interface StravaInterface {

  /**
   * Authenticate the application and set an access token for API requests.
   *
   * @return bool
   */
  public function authenticate();

  /**
   * Check if this app is completely authenticated for API requests.
   *
   * @return bool
   */
  public function isAuthenticated();

  /**
   * Authorize function that creates the authorization link or gets the
   * authorization token.
   *
   * @return mixed
   */
  public function getAuthorizationUrl();

  /**
   * Retrieve an access token from the API.
   *
   * @param $code
   *
   * @return \League\OAuth2\Client\Token\AccessToken
   */
  public function getAccessToken($code);

  /**
   * Check if there is an access token present in session data.
   *
   * @return bool
   */
  public function checkAccessToken();

  /**
   * Store access token in user's private session data.
   */
  public function storeAccessToken();

  /**
   * Delete access token from user's private session data.
   */
  public function deleteAccessToken();

  /**
   * Get an API client to perform API requests.
   *
   * @return OAuth
   */
  public function getApiClient();

}
