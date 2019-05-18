<?php

namespace Drupal\bigcommerce;

use BigCommerce\Api\v3\Api\CartApi;
use BigCommerce\Api\v3\Api\CatalogApi;
use BigCommerce\Api\v3\Api\ChannelsApi;
use BigCommerce\Api\v3\Api\SitesApi;
use BigCommerce\Api\v3\ApiClient;
use Drupal\bigcommerce\API\Configuration;
use Drupal\bigcommerce\Exception\UnconfiguredException;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Create BigCommerce clients.
 */
class ClientFactory {

  /**
   * Basic API setting used by the BigCommerce SDK.
   *
   * @var \Drupal\bigcommerce\API\Configuration
   */
  protected $apiConfig;

  /**
   * API Connection used by the BigCommerce SDK.
   *
   * @var \BigCommerce\Api\v3\ApiClient
   */
  protected $baseClient;

  /**
   * Drupal config factory for API settings.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * ClientFactory constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Drupal config factory for getting API settings.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Gets a BigCommerce SDK cart object.
   *
   * @return \BigCommerce\Api\v3\Api\CartApi
   *   BigCommerce API cart object.
   */
  public function getCart() {
    return new CartApi($this->getBaseClient());
  }

  /**
   * Gets a BigCommerce SDK catalog object.
   *
   * @return \BigCommerce\Api\v3\Api\CatalogApi
   *   BigCommerce API catalog object.
   */
  public function getCatalog() {
    return new CatalogApi($this->getBaseClient());
  }

  /**
   * Gets a BigCommerce SDK channels object.
   *
   * @return \BigCommerce\Api\v3\Api\ChannelsApi
   *   BigCommerce API channels object.
   */
  public function getChannels() {
    return new ChannelsApi($this->getBaseClient());
  }

  /**
   * Gets a BigCommerce SDK sites object.
   *
   * @return \BigCommerce\Api\v3\Api\SitesApi
   *   BigCommerce API sites object.
   */
  public function getSites() {
    return new SitesApi($this->getBaseClient());
  }

  /**
   * Gets a BigCommerce SDK ApiClient, use for internal connection logic.
   *
   * @return \BigCommerce\Api\v3\ApiClient
   *   BigCommerce API Client, used to make API calls.
   */
  protected function getBaseClient() {
    if (!$this->baseClient) {
      $this->baseClient = new ApiClient($this->getConfiguration());
    }
    return $this->baseClient;
  }

  /**
   * Gets the modified BigCommerce configuration object.
   *
   * @return \Drupal\bigcommerce\API\Configuration
   *   Returns an extended BigCommerce API object that holds client ID
   *   and client secret.
   */
  protected function getConfiguration() {
    if (!$this->apiConfig) {
      $config = $this->configFactory->get('bigcommerce.settings');
      if (!$config->get('api.path')) {
        throw new UnconfiguredException('BigCommerce API is not configured');
      }
      if (!$this->entityTypeManager->getStorage('commerce_store')->loadDefault()) {
        throw new UnconfiguredException('BigCommerce requires a default commerce store');
      }

      $this->apiConfig = static::createApiConfiguration($config->get('api'));
    }
    return $this->apiConfig;
  }

  /**
   * Creates a BigCommerce configuration object based on an array of settings.
   *
   * @param array $settings
   *   An array of BigCommerce API settings.
   *
   * @return \Drupal\bigcommerce\API\Configuration
   *   Returns an extended BigCommerce API object that holds client ID
   *   and client secret.
   */
  public static function createApiConfiguration(array $settings) {
    $api_config = new Configuration();
    $api_config
      ->setHost(rtrim($settings['path'], '/\\'))
      ->setClientId($settings['client_id'])
      ->setAccessToken($settings['access_token'])
      ->setClientSecret($settings['client_secret'])
      ->setCurlTimeout($settings['timeout'] ?? 15);
    // Supporting testing the API with a stub.
    if ($test_prefix = drupal_valid_test_ua()) {
      $api_config->setUserAgent(drupal_generate_test_ua($test_prefix));
    }
    return $api_config;
  }

}
