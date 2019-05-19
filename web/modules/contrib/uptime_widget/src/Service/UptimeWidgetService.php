<?php

namespace Drupal\uptime_widget\Service;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Class UptimeWidgetService.
 *
 * This service provides Uptime Robot APIv2 integration.
 *
 * @see https://uptimerobot.com/api
 *
 * @package Drupal\uptime_widget\Service
 */
class UptimeWidgetService {

  use StringTranslationTrait;

  /**
   * The uptime_widget.settings config object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The http_client service.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * Logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The state key value store.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a UptimeWidgetService object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The Guzzle HTTP client.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    ClientInterface $http_client,
    LoggerInterface $logger,
    StateInterface $state) {
    $this->config = $config_factory->getEditable('uptime_widget.settings');
    $this->httpClient = $http_client;
    $this->logger = $logger;
    $this->state = $state;
  }

  /**
   * Service method to send POST request.
   *
   * This method uses to send POST requests to Uptime Robot.
   *
   * @param string $method_name
   *   The API method name.
   * @param array $data
   *   (optional) Structured POST body data.
   *   API key will be added from configs.
   *
   * @return array|null
   *   The JSON decoded body to array, or NULL if an error occurs.
   */
  public function sendPost($method_name, array $data = []) {
    $base_api_uri = 'https://api.uptimerobot.com/v2/';
    $data = $data + [
      'api_key' => $this->config ? $this->config->get('api_key') : '',
      'format' => 'json',
    ];
    try {
      $response = $this->httpClient->post($base_api_uri . $method_name, [
        'headers' => [
          'Content-Type' => "application/x-www-form-urlencoded",
          'Cache-Control' => "no-cache",
        ],
        'form_params' => $data,
      ]);
      return Json::decode((string) $response->getBody());
    }
    catch (\Throwable $e) {
      $this->logger->error($this->t('Error when trying to send POST request to UptimeRobot. Error message: @message', [
        '@message' => $e->getMessage(),
      ]));
    }
    return NULL;
  }

  public function fetchAccountDetails() {
    $account = $this->sendPost('getAccountDetails');
    if ($account['stat'] == 'ok') {
      $this->config
        ->set('account_up_monitors', $account['account']['up_monitors'])
        ->set('account_down_monitors', $account['account']['down_monitors'])
        ->set('account_paused_monitors', $account['account']['paused_monitors'])
        ->save();
      $this->state->set('uptime_widget.account', [
        'email' => $account['account']['email'],
      ]);
    }
    else {
      $this->logger->error($this->t('Error when trying to fetch UptimeRobot account details.'));
    }
  }

}
