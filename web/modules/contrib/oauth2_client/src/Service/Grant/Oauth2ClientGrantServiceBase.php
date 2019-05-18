<?php

namespace Drupal\oauth2_client\Service\Grant;

use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\State\StateInterface;
use Drupal\oauth2_client\Plugin\Oauth2Client\Oauth2ClientPluginInterface;
use Drupal\oauth2_client\PluginManager\Oauth2ClientPluginManagerInterface;
use Drupal\oauth2_client\Service\Oauth2ClientServiceBase;
use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Token\AccessToken;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Base class for OAuth2 Client grant services.
 */
abstract class Oauth2ClientGrantServiceBase extends Oauth2ClientServiceBase implements Oauth2ClientGrantServiceInterface {
  /**
   * The Request Stack.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * The Drupal state.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The URL generator service.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * The OAuth2 Client plugin manager.
   *
   * @var \Drupal\oauth2_client\PluginManager\Oauth2ClientPluginManagerInterface
   */
  protected $oauth2ClientPluginManager;

  /**
   * Client provider cache
   *
   * @var array
   */
  protected $clientProviderCache;

  /**
   * Construct an OAuth2Client object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The Request Stack.
   * @param \Drupal\Core\State\StateInterface $state
   *   The Drupal state.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $urlGenerator
   *   The URL generator service.
   * @param \Drupal\oauth2_client\PluginManager\Oauth2ClientPluginManagerInterface $oauth2ClientPluginManager
   *   The OAuth2 Client plugin manager.
   */
  public function __construct(
    RequestStack $requestStack,
    StateInterface $state,
    UrlGeneratorInterface $urlGenerator,
    Oauth2ClientPluginManagerInterface $oauth2ClientPluginManager
  ) {
    $this->currentRequest = $requestStack->getCurrentRequest();
    $this->state = $state;
    $this->urlGenerator = $urlGenerator;
    $this->oauth2ClientPluginManager = $oauth2ClientPluginManager;
    $this->clientProviderCache = array();
  }

  /**
   * Creates a new provider object.
   *
   * @param string $clientId
   *   The client for which a provider should be created.
   *
   * @return \League\OAuth2\Client\Provider\GenericProvider
   *   The provider of the OAuth2 Server.
   *
   * @throws \Drupal\oauth2_client\Exception\InvalidOauth2ClientException
   *   Exception thrown when trying to retrieve a non-existent OAuth2 Client.
   */
  protected function getProvider($clientId) {
    if (isset($this->clientProviderCache[$clientId])) {
      $provider = $this->clientProviderCache[$clientId];
    } else {
      $client = $this->getClient($clientId);

      $provider = new GenericProvider([
        'clientId' => $client->getClientId(),
        'clientSecret' => $client->getClientSecret(),
        'redirectUri' => $this->getRedirectUri($client),
        'urlAuthorize' => $client->getAuthorizationUri(),
        'urlAccessToken' => $client->getTokenUri(),
        'urlResourceOwnerDetails' => $client->getResourceUri(),
        'scopes' => $client->getScopes(),
        'scopeSeparator' => $client->getScopeSeparator(),
      ]);
      $this->clientProviderCache[$clientId] = $provider;
    }
    return $provider;
  }

  /**
   * Store an access token to the Drupal state.
   *
   * @param string $clientId
   *   The client for which a provider should be created.
   * @param \League\OAuth2\Client\Token\AccessToken $accessToken
   *   The Access Token to be stored.
   */
  protected function storeAccessToken($clientId, AccessToken $accessToken) {
    $this->state->set('oauth2_client_access_token-' . $clientId, $accessToken);
  }

  /**
   * Retrieves the local redirect URI used for OAuth2 authentication.
   *
   * @param \Drupal\oauth2_client\Plugin\Oauth2Client\Oauth2ClientPluginInterface $client
   *   The OAuth2 Client Plugin for which the redirect URI should be retrieved.
   *
   * @return string
   *   The redirect URI for the given OAuth2 Server client.
   */
  private function getRedirectUri(Oauth2ClientPluginInterface $client) {
    return $this->urlGenerator->generateFromRoute('<current>', [], ['absolute' => TRUE]);
  }

}
