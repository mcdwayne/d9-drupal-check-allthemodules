<?php

namespace Drupal\pocket\Client;

use Drupal\Core\Url;
use Drupal\pocket\AccessToken;

interface PocketAuthClientInterface {

  /**
   * Generate a request token and automatically process the redirect.
   *
   * Note: Callback and state data must be serializable.
   *
   * @param callable $callback
   *   The callback to run on the finished access token.
   * @param array    $state
   *   Optional state information to store with the request.
   *
   * @return \Drupal\Core\Url
   *   Returns the Pocket URL to which to redirect the user for authorization.
   */
  public function authorize(callable $callback, array $state = []): Url;

  /**
   * Generate a request token that redirects to a specific URL.
   *
   * @param \Drupal\Core\Url $redirect
   *   A URL to redirect to after authorization.
   * @param string           $state
   *   An optional state string to identify responses.
   *
   * @return string
   *   The access token.
   */
  public function getRequestToken(Url $redirect, string $state = NULL): string;

  /**
   * Turn a request token into an access token.
   *
   * @param string $requestToken
   *
   * @return \Drupal\pocket\AccessToken
   *
   * @throws \Drupal\pocket\Exception\UnauthorizedException
   * @throws \Drupal\pocket\Exception\AccessDeniedException
   */
  public function getAccessToken(string $requestToken): AccessToken;

}
