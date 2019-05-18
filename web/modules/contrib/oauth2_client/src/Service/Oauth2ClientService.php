<?php

namespace Drupal\oauth2_client\Service;

use Drupal\Core\State\StateInterface;
use Drupal\oauth2_client\PluginManager\Oauth2ClientPluginManagerInterface;
use Drupal\oauth2_client\Service\Grant\Oauth2ClientGrantServiceInterface;

/**
 * The OAuth2 Client service.
 */
class Oauth2ClientService extends Oauth2ClientServiceBase {

  /**
   * The OAuth2 Client plugin manager.
   *
   * @var \Drupal\oauth2_client\PluginManager\Oauth2ClientPluginManagerInterface
   */
  protected $oauth2ClientPluginManager;

  /**
   * The Drupal state.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * An array of OAuth2 Client grant services.
   *
   * @var array
   */
  protected $grantServices = [];

  /**
   * Constructs an Oauth2ClientService object.
   *
   * @param \Drupal\oauth2_client\PluginManager\Oauth2ClientPluginManagerInterface $oauth2ClientPluginManager
   *   The Oauth2 Client plugin manager.
   * @param \Drupal\Core\State\StateInterface $state
   *   The Drupal state.
   * @param \Drupal\oauth2_client\Service\Grant\Oauth2ClientGrantServiceInterface $authorizationCodeGrantService
   *   The authorization code grant service.
   * @param \Drupal\oauth2_client\Service\Grant\Oauth2ClientGrantServiceInterface $clientCredentialsGrantService
   *   The client credentials grant service.
   * @param \Drupal\oauth2_client\Service\Grant\Oauth2ClientGrantServiceInterface $refreshTokenGrantService
   *   The refresh token grant service.
   * @param \Drupal\oauth2_client\Service\Grant\Oauth2ClientGrantServiceInterface $resourceOwnersCredentialsGrantService
   *   The resource owner's credentials grant service.
   */
  public function __construct(
    Oauth2ClientPluginManagerInterface $oauth2ClientPluginManager,
    StateInterface $state,
    Oauth2ClientGrantServiceInterface $authorizationCodeGrantService,
    Oauth2ClientGrantServiceInterface $clientCredentialsGrantService,
    Oauth2ClientGrantServiceInterface $refreshTokenGrantService,
    Oauth2ClientGrantServiceInterface $resourceOwnersCredentialsGrantService
  ) {
    $this->oauth2ClientPluginManager = $oauth2ClientPluginManager;
    $this->state = $state;
    $this->grantServices = [
      'authorization_code' => $authorizationCodeGrantService,
      'client_credentials' => $clientCredentialsGrantService,
      'refresh_token' => $refreshTokenGrantService,
      'resource_owner' => $resourceOwnersCredentialsGrantService,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessToken($clientId) {
    $access_token = $this->retrieveAccessToken($clientId);
    if (!$access_token || ($access_token->getExpires() && $access_token->hasExpired())) {
      $client = $this->getClient($clientId);

      switch ($client->getGrantType()) {
        case 'authorization_code':
          $access_token = $this->getAuthorizationCodeAccessToken($clientId);
          break;

        case 'client_credentials':
          $access_token = $this->getClientCredentialsAccessToken($clientId);
          break;

        case 'resource_owner':
          $access_token = $this->getResourceOwnersCredentialsAccessToken($clientId);
          break;
      }
    }

    return $access_token;
  }

  /**
   * {@inheritdoc}
   */
  public function getProvider($clientId) {
    $client = $this->getClient($clientId);
    switch ($client->getGrantType()) {
      case 'client_credentials':
        $provider = $this->getClientCredentialsProvider($clientId);
        break;
      case 'resource_owner':
        $provider = $this->getResourceOwnersCredentialsProvider($clientId);
        break;
      case 'authorization_code':
      default:
        $provider = $this->getAuthorizationCodeProvider($clientId);
        break;
    }
    return $provider;
  }

  /**
   * Retrieves an access token for the 'authorization_code' grant type.
   *
   * @return \League\OAuth2\Client\Token\AccessTokenInterface
   *   The Access Token for the given client ID.
   */
  private function getAuthorizationCodeAccessToken($clientId) {
    $stored_token = $this->retrieveAccessToken($clientId);
    if ($stored_token) {
      if ($stored_token->getExpires() && $stored_token->hasExpired()) {
        if (empty($stored_token->getRefreshToken())) {
          # Token is expired but we have no refresh_token. Just get a new one.
          $access_token = NULL;
        }
        else {
          $access_token = $this->grantServices['refresh_token']->getAccessToken($clientId);
        }
      }
      else {
        $access_token = $stored_token;
      }
    }
    if (empty($access_token)) {
      $access_token = $this->grantServices['authorization_code']->getAccessToken($clientId);
    }

    return $access_token;
  }

  /**
   * Retrieves the league/oauth2-client provider for the 'authorization_code'
   * grant type.
   *
   * @return \League\OAuth2\Client\Provider\AbstractProvider
   *   The Provider for the given client ID.
   */
  private function getAuthorizationCodeProvider($clientId) {
    return $this->grantServices['authorization_code']->getGrantProvider($clientId);
  }

  /**
   * Retrieves an access token for the 'client_credentials' grant type.
   *
   * @return \League\OAuth2\Client\Token\AccessTokenInterface
   *   The Access Token for the given client ID.
   */
  private function getClientCredentialsAccessToken($clientId) {
    $access_token = $this->retrieveAccessToken($clientId);

    if (!$access_token) {
      $access_token = $this->grantServices['client_credentials']->getAccessToken($clientId);
    }

    return $access_token;
  }

  /**
   * Retrieves the league/oauth2-client provider for the 'client_credentials'
   * grant type.
   *
   * @return \League\OAuth2\Client\Provider\AbstractProvider
   *   The Provider for the given client ID.
   */
  private function getClientCredentialsProvider($clientId) {
    return $this->grantServices['client_credentials']->getGrantProvider($clientId);
  }

  /**
   * Retrieves an access token for the 'resource_owner' grant type.
   *
   * @return \League\OAuth2\Client\Token\AccessTokenInterface
   *   The Access Token for the given client ID.
   */
  private function getResourceOwnersCredentialsAccessToken($clientId) {
    $access_token = $this->retrieveAccessToken($clientId);

    if (!$access_token) {
      $access_token = $this->grantServices['resource_owner']->getAccessToken($clientId);
    }

    return $access_token;
  }

  /**
   * Retrieves the league/oauth2-client provider for the 'resource_owner' grant
   * type.
   *
   * @return \League\OAuth2\Client\Provider\AbstractProvider
   *   The Provider for the given client ID.
   */
  private function getResourceOwnersCredentialsProvider($clientId) {
    return $this->grantServices['resource_owner']->getGrantProvider($clientId);
  }

}
