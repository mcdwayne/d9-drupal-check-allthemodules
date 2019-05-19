<?php

namespace Drupal\social_auth_strava;

use Drupal\social_auth\AuthManager\OAuth2Manager;
use Strava\API\OAuth;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Manages the authentication requests.
 */
class StravaAuthManager extends OAuth2Manager {

  /**
   * The request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The Strava client.
   *
   * @var OAuth
   */
  protected $client;

  /**
   * Access token returned by Strava for authentication.
   *
   * @var string
   */
  protected $accessToken;

  /**
   * StravaLoginManager constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   Used to get the parameter code returned by Strava.
   */
  public function __construct(RequestStack $request) {
    $this->request = $request->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate() {
    $this->accessToken = $this->client->getAccessToken('authorization_code',
      ['code' => $_GET['code']]);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessToken() {
    return $this->accessToken;
  }

  /**
   * Returns the user information.
   *
   * @return mixed
   *   The user data.
   */
  public function getUserInfo() {
    $values = $this->accessToken->getValues();
    return $this->client->userDetails((object) $values['athlete'], $this->getAccessToken());
  }
}
