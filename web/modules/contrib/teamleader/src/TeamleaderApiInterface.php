<?php

namespace Drupal\teamleader;

use League\OAuth2\Client\Token\AccessToken;

/**
 * Interface for TeamleaderApi service.
 */
interface TeamleaderApiInterface {

  /**
   * Get client object, to interact with Teamleader data repositories.
   *
   * @return \Nascom\TeamleaderApiClient\Teamleader|bool
   *   Teamleader client object.
   */
  public function getClient();

  /**
   * Start the Teamleader OAuth2 authorization flow.
   *
   * @return \Drupal\Core\Url
   *   The Teamleader authorization URL to redirect to.
   */
  public function startAuthorization();

  /**
   * Finish the Teamleader OAuth2 authorization flow.
   */
  public function finishAuthorization();

  /**
   * Get a Teamleader API client.
   *
   * @return \Nascom\TeamleaderApiClient\Http\ApiClient\ApiClient|null
   *   The Teamleader API client object.
   */
  public function getApiClient();

  /**
   * OAuth2 refresh token callback.
   *
   * @param \League\OAuth2\Client\Token\AccessToken $token
   *   The refreshed token.
   *
   * @see \Nascom\TeamleaderApiClient\Http\Guzzle\Middleware\AuthenticationMiddleware
   */
  public function refreshToken(AccessToken $token);

}
