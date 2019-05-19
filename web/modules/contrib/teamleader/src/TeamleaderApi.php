<?php

namespace Drupal\teamleader;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use Nascom\OAuth2\Client\Provider\Teamleader as TeamleaderProvider;
use Nascom\TeamleaderApiClient\Http\Guzzle\GuzzleApiClientFactory;
use Nascom\TeamleaderApiClient\Teamleader;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;


/**
 * Teamleader Integration service.
 */
class TeamleaderApi implements TeamleaderApiInterface {

  /**
   * The Teamleader config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $teamleaderConfig;

  /**
   * Drupal state.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The URL generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * The current request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Constructs a TeamleaderApi class.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The URL Generator service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   *
   */
  public function __construct(ConfigFactoryInterface $config_factory, StateInterface $state, UrlGeneratorInterface $url_generator, RequestStack $request_stack) {
    $this->teamleaderConfig = $config_factory->get('teamleader.settings');
    $this->state = $state;
    $this->urlGenerator = $url_generator;
    $this->request = $request_stack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public function getClient() {
    $api_client = $this->getApiClient();
    if (!$api_client) {
      return FALSE;
    }
    return Teamleader::withDefaultSerializer($api_client);
  }

  /**
   * {@inheritdoc}
   */
  public function startAuthorization() {
    if (!empty($this->teamleaderConfig->get('credentials.client_id')) && !empty($this->teamleaderConfig->get('credentials.client_secret'))) {
      // Clear the old access token.
      $this->state->set('teamleader_access_token', '');
      $session = $this->request->getSession();
      $session->remove('oauth2state');

      // Create provider.
      $provider = $this->createTeamleaderProvider();

      // Retrieve the Teamleader authorization URL to redirect to.
      $authorizationUrl = $provider->getAuthorizationUrl();
      $session->set('oauth2state', $provider->getState());
      return Url::fromUri($authorizationUrl, ['absolute' => TRUE]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function finishAuthorization() {
    if ($this->request->query->has('code')) {
      $session = $this->request->getSession();
      if (empty($this->request->query->get('state')) || ($session->has('oauth2state') && $this->request->query->get('state') !== $session->get('oauth2state'))) {
        if ($session->has('oauth2state')) {
          $session->remove('oauth2state');
        }
        drupal_set_message('Invalid state');
      }
      else {
        try {
          // Create provider.
          $provider = $this->createTeamleaderProvider();

          $accessToken = $provider->getAccessToken('authorization_code', ['code' => $this->request->query->get('code')]);
          $this->state->set('teamleader_access_token', $accessToken->jsonSerialize());
          drupal_set_message('Successfully connected.');

          // @TODO: improve?
          $redirect_path = $this->urlGenerator->generateFromRoute('teamleader.settings', [], ['absolute' => TRUE]);
          return new RedirectResponse($redirect_path);
        }
        catch (IdentityProviderException $e) {
          drupal_set_message('Connection failed.');
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getApiClient() {
    $accessToken = $this->state->get('teamleader_access_token', '');
    if ($accessToken) {
      try {
        // Create provider.
        $provider = $this->createTeamleaderProvider();

        return GuzzleApiClientFactory::create(
          $provider,
          new AccessToken($accessToken),
          ['callback' => [$this, 'refreshToken']]
        );

      }
      catch (\Exception $e) {
        return NULL;
      }
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function refreshToken(AccessToken $token) {
    $this->state->set('teamleader_access_token', $token->jsonSerialize());
  }

  /**
   * Create a new Teamleader OAuth2 Provider based on module config.
   *
   * @return \Nascom\OAuth2\Client\Provider\Teamleader
   *   The OAuth2 Provider object.
   */
  protected function createTeamleaderProvider() {
    return new TeamleaderProvider([
      'clientId' => $this->teamleaderConfig->get('credentials.client_id'),
      'clientSecret' => $this->teamleaderConfig->get('credentials.client_secret'),
      'redirectUri' => $this->urlGenerator->generateFromRoute('teamleader.settings', [], ['absolute' => TRUE]),
    ]);
  }

}
