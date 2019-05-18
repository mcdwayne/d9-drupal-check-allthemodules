<?php

namespace Drupal\janrain_connect_settings\Service;

use Drupal\janrain_connect\Service\JanrainConnectConnector;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * JanrainConnect User Class.
 *
 * Responsible for integration with User module from Drupal Core. Creates the
 * User entity fields dynamically based on configuration and persists the Drupal
 * User.
 */
class JanrainConnectSettingsService {

  use StringTranslationTrait;

  /**
   * JanrainConnectConnector.
   *
   * @var \Drupal\janrain_connect\Service\JanrainConnectConnector
   */
  protected $janrainConnector;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactory $config_factory, LoggerChannelFactoryInterface $logger_factory, JanrainConnectConnector $janrain_connector) {
    $this->config = $config_factory->get('janrain_connect.settings');
    $this->logger = $logger_factory->get('janrain_connect');
    $this->janrainConnector = $janrain_connector;
  }

  /**
   * Method to get config on Janrain.
   *
   * @param string $key
   *   Janrain setting key.
   *
   * @return bool|array|string
   *   Janrain setting key, all or FALSE.
   */
  public function getJanrainSettings($key = NULL) {

    $result = $this->janrainConnector->getJanrainSettings();

    if (!empty($result['has_errors'])) {
      return NULL;
    }

    $result = $result['result'];

    if (empty($key)) {
      return $result;
    }

    if (isset($result[$key])) {
      return $result[$key];
    }

    return NULL;
  }

}
