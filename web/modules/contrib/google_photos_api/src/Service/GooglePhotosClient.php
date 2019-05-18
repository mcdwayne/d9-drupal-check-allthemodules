<?php

namespace Drupal\google_photos_api\Service;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\google_api_client\Service\GoogleApiClient;
use Google_Service_PhotosLibrary;

/**
 * Class Google Photos API Client Service.
 *
 * @package Drupal\google_photos_api\Service
 */
class GooglePhotosClient {

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  public $loggerFactory;

  /**
   * Uneditable Config.
   *
   * @var \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   */
  private $config;

  /**
   * Uneditable Tokens Config.
   *
   * @var \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   */
  private $configTokens;

  /**
   * Google API Client.
   *
   * @var \Drupal\google_api_client\Service\GoogleApiClient
   */
  private $googleApiClient;

  /**
   * Google Service Photos Library.
   *
   * @var \Google_Service_PhotosLibrary
   */
  public $googleServicePhotosLibrary;

  /**
   * Callback Controller constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config
   *   An instance of ConfigFactory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   LoggerChannelFactoryInterface.
   * @param \Drupal\google_api_client\Service\GoogleApiClient $googleApiClient
   *   GoogleApiClient.
   */
  public function __construct(ConfigFactory $config,
                              LoggerChannelFactoryInterface $loggerFactory,
                              GoogleApiClient $googleApiClient) {
    $this->config = $config->get('google_api_client.settings');
    $this->configTokens = $config->get('google_api_client.tokens');
    $this->loggerFactory = $loggerFactory;

    $this->googleApiClient = $googleApiClient;
    $this->googleServicePhotosLibrary = $this->getGoogleServicePhotosLibrary();
  }

  /**
   * Helper method to getGoogleMyBusinessClient.
   *
   * @return \Google_Service_PhotosLibrary
   *   Google Service Photos Library.
   */
  private function getGoogleServicePhotosLibrary() {
    // See https://developers.google.com/photos/library/guides/authentication-authorization
    // Access to both the .appendonly and .readonly scopes.
    // Doesn't include .sharing.
    $this->googleApiClient->googleClient->setScopes(["https://www.googleapis.com/auth/photoslibrary"]);

    // Set up the Photos Library Client that interacts with the API.
    $googleServicePhotosLibrary = new Google_Service_PhotosLibrary($this->googleApiClient->googleClient);
    return $googleServicePhotosLibrary;
  }

}
