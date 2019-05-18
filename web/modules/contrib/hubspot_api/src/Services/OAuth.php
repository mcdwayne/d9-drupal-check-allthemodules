<?php

namespace Drupal\hubspot_api\Services;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Url;
use GuzzleHttp\ClientInterface;
use SevenShores\Hubspot\Exceptions\BadRequest;
use SevenShores\Hubspot\Http\Client;
use SevenShores\Hubspot\Resources\OAuth2;

/**
 * Provides an OAuth2 service for Hubspot.
 */
class OAuth {

  /**
   * HubSpot's API Login URL.
   */
  const API_LOGIN_URL = 'https://app.hubspot.com';

  /**
   * HubSpot's API domain.
   */
  const API_URL = 'https://app.hubapi.com';

  /**
   * HubSpot's API endpoint for initiating OAuth access.
   */
  const API_INIT_TOKEN_ENDPOINT = '/oauth/authorize';

  /**
   * HubSpot's API endpoint for refreshing OAuth access token.
   */
  const API_TOKEN_ENDPOINT = '/oauth/v1/token';

  /**
   * The config factory to use.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|null
   */
  protected $configFactory;

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a new OAuth Service.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   A Guzzle client object.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ClientInterface $http_client, LoggerChannelFactoryInterface $logger_factory) {
    $this->configFactory = $config_factory;
    $this->httpClient = $http_client;
    $this->logger = $logger_factory->get('hubspot_api');
  }

  /**
   * Gets the OAuth token from the Hubspot code.
   *
   * @param string $code
   *   The code returned after authorizing the Hubspot environment for this app.
   *
   * @return mixed|bool
   *   The token data, or false otherwise.
   */
  public function getTokensByCode($code) {
    $config = $this->configFactory->getEditable('hubspot_api.settings');
    $client_id = $config->get('client_id');
    $client_secret = $config->get('client_secret');
    $client = new Client(['key' => $client_secret]);
    $oauth = new OAuth2($client);
    try {
      $tokens = $oauth->getTokensByCode(
        $client_id,
        $client_secret,
        Url::fromRoute('hubspot_api.oauth_redirect', [], ['absolute' => TRUE])->toString(),
        $code
      );
    } catch (BadRequest $e) {
      \Drupal::logger('hubspot_api')
        ->error(
          'Failed to get OAuth tokens: %error',
          ['%error' => $e->getMessage()]
        );
      return FALSE;
    }

    return $tokens->getData();
  }

  /**
   * Refresh the OAuth token.
   *
   * @return mixed|bool
   *   The token data, or false otherwise.
   */
  public function getTokensByRefresh() {
    $config = $this->configFactory->getEditable('hubspot_api.settings');
    $client_id = $config->get('client_id');
    $client_secret = $config->get('client_secret');
    $client = new Client(['key' => $client_secret]);
    $oauth = new OAuth2($client);
    try {
      $tokens = $oauth->getTokensByRefresh(
        $client_id,
        $client_secret,
        $config->get('refresh_token')
      );
    } catch (BadRequest $e) {
      \Drupal::logger('hubspot_api')
        ->error(
          'Failed to refresh OAuth tokens: %error',
          ['%error' => $e->getMessage()]
        );
      return FALSE;
    }

    $this->saveTokens($tokens->getData());

    return $tokens->access_token;
  }

  /**
   * Saves the OAuth tokens to be used for later API calls.
   *
   * @param \stdClass $tokens
   *   The options use by the token endpoint. See API docs for more info.
   *
   * @see https://developers.hubspot.com/docs/methods/oauth2/get-access-and-refresh-tokens
   * @see https://developers.hubspot.com/docs/methods/oauth2/refresh-access-token
   *
   * @return bool
   *   Tokens were properly saved.
   */
  public function saveTokens($tokens) {
    $config = $this->configFactory->getEditable('hubspot_api.settings');
    return (bool) $config
      ->set('access_token', $tokens->access_token)
      ->set('refresh_token', $tokens->refresh_token)
      ->set('expire_date', $tokens->expires_in + time())
      ->save();
  }
}
