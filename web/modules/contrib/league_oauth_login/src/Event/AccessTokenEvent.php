<?php

namespace Drupal\league_oauth_login\Event;

use League\OAuth2\Client\Token\AccessTokenInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class.
 */
class AccessTokenEvent extends Event {

  /**
   * Request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Access token.
   *
   * @var \League\OAuth2\Client\Token\AccessTokenInterface
   */
  protected $accessToken;

  /**
   * LoginWithCodeEvent constructor.
   */
  public function __construct(Request $request, AccessTokenInterface $accessToken) {
    $this->request = $request;
    $this->accessToken = $accessToken;
  }

  /**
   * Get the request.
   *
   * @return \Symfony\Component\HttpFoundation\Request
   *   The current request.
   */
  public function getRequest() {
    return $this->request;
  }

  /**
   * Get access token.
   *
   * @return \League\OAuth2\Client\Token\AccessTokenInterface
   *   Access token.
   */
  public function getAccessToken() {
    return $this->accessToken;
  }

}
