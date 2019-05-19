<?php

namespace Drupal\strava\Api;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Http\Message\ResponseInterface;

class OAuth extends AbstractProvider {

  /**
   * @see AbstractProvider::__construct
   * @param array $options
   */
  public function __construct($options)
  {
    parent::__construct($options);
    $this->headers = array(
      'Authorization' => 'Bearer'
    );
  }

  /**
   * Returns the base URL for authorizing a client.
   *
   * Eg. https://oauth.service.com/authorize
   *
   * @return string
   */
  public function getBaseAuthorizationUrl() {
    return 'https://www.strava.com/oauth/authorize';
  }

  /**
   * Returns the base URL for requesting an access token.
   *
   * Eg. https://oauth.service.com/token
   *
   * @param array $params
   * @return string
   */
  public function getBaseAccessTokenUrl(array $params) {
    return 'https://www.strava.com/oauth/token';
  }

  /**
   * Returns the URL for requesting the resource owner's details.
   *
   * @param AccessToken $token
   * @return string
   */
  public function getResourceOwnerDetailsUrl(AccessToken $token) {
    return '';
  }

  /**
   * Returns the default scopes used by this provider.
   *
   * This should only be the scopes that are required to request the details
   * of the resource owner, rather than all the available scopes.
   *
   * @return array
   */
  protected function getDefaultScopes() {
    return array('write');
  }

  /**
   * Checks a provider response for errors.
   *
   * @throws IdentityProviderException
   * @param  ResponseInterface $response
   * @param  array|string $data Parsed response data
   * @return void
   */
  protected function checkResponse(ResponseInterface $response, $data) {

  }

  /**
   * Generates a resource owner object from a successful resource owner
   * details request.
   *
   * @param  array $response
   * @param  AccessToken $token
   * @return ResourceOwnerInterface
   */
  protected function createResourceOwner(array $response, AccessToken $token) {

  }
}