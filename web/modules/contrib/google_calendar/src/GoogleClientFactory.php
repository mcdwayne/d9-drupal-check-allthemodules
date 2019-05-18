<?php

namespace Drupal\google_calendar;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

use Google_Client;
use GuzzleHttp\Client as GuzzleHttpClient;

/**
 * Class GoogleClientFactory.
 *
 * @package Drupal\google_calendar
 */
class GoogleClientFactory {

  /**
   */
  protected $configFactory;

  /**
   */
  protected $loggerFactory;

  /**
   */
  protected $logger;

  /**
   * GoogleClientFactory constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   */
  public function __construct(ConfigFactory $configFactory, LoggerChannelFactoryInterface $loggerFactory) {
    $this->configFactory = $configFactory;
    $this->loggerFactory = $loggerFactory;
    $this->logger = $this->loggerFactory->get('google_calendar');
  }

  /**
   * Return a configured Client object.
   */
  public function get() {
    $client = new Google_Client();
    /** @var \Drupal\google_calendar\GoogleCalendarSecretsFileInterface $credentials */
    $credentials = \Drupal::service('google_calendar.secrets_file');
    $secret_file = $credentials->getFilePath();
    $client->setApplicationName('CHBC Calendar Services');
    $client->setAuthConfig($secret_file);

    $scopes = [
      \Google_Service_Calendar::CALENDAR,
      \Google_Service_Calendar::CALENDAR_READONLY,
    ];
    $client->setScopes($scopes);

    // config HTTP client and config timeout
    $client->setHttpClient(new GuzzleHttpClient([
      'timeout' => 10,
      'connect_timeout' => 10,
      'verify' => false
    ]));

    return $client;
  }
}
