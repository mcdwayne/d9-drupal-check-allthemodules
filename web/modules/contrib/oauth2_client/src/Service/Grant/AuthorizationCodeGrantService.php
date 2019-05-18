<?php

namespace Drupal\oauth2_client\Service\Grant;

use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\oauth2_client\PluginManager\Oauth2ClientPluginManagerInterface;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Handles Authorization Grants for the OAuth2 Client module.
 */
class AuthorizationCodeGrantService extends Oauth2ClientGrantServiceBase {

  /**
   * The Drupal tempstore.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStore
   */
  protected $tempstore;

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
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $tempstoreFactory
   *   The Drupal private tempstore factory.
   */
  public function __construct(
    RequestStack $requestStack,
    StateInterface $state,
    UrlGeneratorInterface $urlGenerator,
    Oauth2ClientPluginManagerInterface $oauth2ClientPluginManager,
    PrivateTempStoreFactory $tempstoreFactory
  ) {
    parent::__construct($requestStack, $state, $urlGenerator, $oauth2ClientPluginManager);

    $this->tempstore = $tempstoreFactory->get('oauth2_client');
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessToken($clientId) {
    $provider = $this->getProvider($clientId);

    // If an authorization code is not set in the URL parameters, get one.
    if (!$this->currentRequest->get('code')) {
      // Get the authorization URL. This also generates the state.
      $authorization_url = $provider->getAuthorizationUrl();

      // Save the state to Drupal's tempstore.
      $this->tempstore->set('oauth2_client_state-' . $clientId, $provider->getState());

      // Redirect to the authorization URL.
      $redirect = new RedirectResponse($authorization_url);
      $redirect->send();
      exit();
    }
    // Check given state against previously stored one to mitigate CSRF attack.
    elseif (!$this->currentRequest->get('state') || $this->currentRequest->get('state') !== $this->tempstore->get('oauth2_client_state-' . $clientId)) {
      // Potential CSRF attack. Bail out.
      $this->tempstore->delete('oauth2_client_state-' . $clientId);
    }
    else {
      try {
        // Try to get an access token using the authorization code grant.
        $accessToken = $provider->getAccessToken('authorization_code', [
          'code' => $this->currentRequest->get('code'),
        ]);

        $this->storeAccessToken($clientId, $accessToken);
      }
      catch (IdentityProviderException $e) {
        watchdog_exception('OAuth2 Client', $e);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getGrantProvider($clientId) {
    return $this->getProvider($clientId);
  }

}
