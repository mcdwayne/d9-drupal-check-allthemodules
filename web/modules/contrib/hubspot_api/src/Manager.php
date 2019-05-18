<?php

namespace Drupal\hubspot_api;

use DateTime;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\hubspot_api\Services\OAuth;
use SevenShores\Hubspot\Factory;

/**
 * Hubspot API Manager
 */
class Manager {

  /**
   * The config for HubSpot.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The Hubspot OAuth service.
   *
   * @var \Drupal\hubspot_api\Services\OAuth
   */
  protected $oauthService;

  /**
   * Constructs a new HubSpot service instance.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\hubspot_api\Services\OAuth $oauth_service
   *   The OAuth service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger_factory, OAuth $oauth_service) {
    $this->config = $config_factory->get('hubspot_api.settings');
    $this->logger = $logger_factory;
    $this->oauthService = $oauth_service;
  }

  /**
   * Creates the Factory object that will be used to make api calls.
   *
   * @return \SevenShores\Hubspot\Factory
   *   The factory object.
   *
   * @throws \Exception
   */
  public function getHandler() {
    $token = $this->config->get('access_token');
    if ($token) {
      $now = new DateTime();
      // The token expires every 6 hours. Adding 15 minutes to give plenty of
      // time for a procedure to finish.
      $now->modify('+15 minutes');
      $expires = clone($now);
      $expires->setTimestamp($this->config->get('expire_date'));
      if ($now > $expires) {
        $token = $this->oauthService->getTokensByRefresh($this->config->get('refresh_token'));
      }

      $hubspot = Factory::createWithToken($token);
      $hubspot->client->oauth2 = TRUE;
      return $hubspot;
    }

    // Use an access key if OAuth is not configured.
    $key = $this->config->get('access_key');
    if (empty($key)) {
      $this->logger->error('Missing Hubspot API configuration.');
      return NULL;
    }

    return Factory::create($key);
  }

}
