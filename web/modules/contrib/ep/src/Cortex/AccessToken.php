<?php

namespace Drupal\ep\Cortex;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\State\StateInterface;
use GuzzleHttp\ClientInterface;

/**
 * Generates access token to interact with Elastic Path Cortex API.
 */
class AccessToken {

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Settings Service.
   *
   * @var \Drupal\Core\Site\Settings
   */
  protected $settings;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Constructs a AccessToken instance.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory.
   * @param \Drupal\Core\Site\Settings $settings
   *   The read-only settings container.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   */
  public function __construct(StateInterface $state, ClientInterface $http_client, LoggerChannelFactoryInterface $logger_factory, Settings $settings, ConfigFactoryInterface $config_factory) {
    $this->state = $state;
    $this->httpClient = $http_client;
    $this->loggerFactory = $logger_factory;
    $this->settings = $settings;
    $this->config = $config_factory->get('ep.settings');
  }

  /**
   * Generates access token & stores to state.
   */
  public function generate() {
    // Fetch the token if it doesn't exist in the Drupal state.
    $cortex_username = $this->settings->get('ep.cortex_username');
    $cortex_password = $this->settings->get('ep.cortex_password');
    $aws_api_id = $this->settings->get('ep.aws_api_id');
    $aws_api_key = $this->settings->get('ep.aws_api_key');

    $oauth_url = $this->config->get('cortex.base_url') . $this->config->get('cortex.oauth2_uri');
    $scope = $this->config->get('cortex.store');
    // Giving a background call to get POST.
    try {
      $http_client = $this->httpClient->post($oauth_url, [
          'body' => 'username=' . $cortex_username . '&password=' . $cortex_password . '&grant_type=password&scope=' . $scope . '&role=REGISTERED&',
          'headers' => [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'x-api-key' => $aws_api_key,
            'x-amzn-apigateway-api-id' => $aws_api_id,
          ],
        ]
      );
      $response = $http_client->getBody()->getContents();
      // If response is not empty then set the authentication token once again.
      if (!empty($response)) {
        $response_decoded = Json::decode($response);
        $this->state->set('ep_cortex_token', $response_decoded['access_token']);
        $this->loggerFactory->get('ep')
          ->notice('Access token has been updated.');
      }
    } catch (\Exception $e) {
      $this->loggerFactory->get('ep')
        ->error('There is an error while getting the access token.' . $e->getMessage());
    }
  }

}
