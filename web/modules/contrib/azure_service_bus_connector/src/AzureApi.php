<?php

namespace Drupal\azure_service_bus_connector;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use WindowsAzure\Common\ServicesBuilder;

/**
 * Class AzureApi.
 *
 * @package Drupal\azure_service_bus_connector
 */
class AzureApi {

  /**
   * The Azure config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The Azure connection string.
   *
   * @var string
   */
  public $connectionString;

  /**
   * The endpoint for the Azure connection string.
   *
   * @var string
   */
  protected $sharedAccessEndpoint;

  /**
   * The access key name for the Azure connection string.
   *
   * @var string
   */
  protected $sharedAccessKeyName;

  /**
   * The access key for the Azure connection string.
   *
   * @var string
   */
  protected $sharedAccessKey;

  /**
   * The logger interface.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  protected $serviceBusRestProxy;

  /**
   * Constructs a \Drupal\azure_service_bus_connector\AzureApi object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The config factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger channel interface.
   *
   * @codeCoverageIgnore
   */
  public function __construct(ConfigFactoryInterface $config, LoggerChannelFactoryInterface $logger) {
    $this->config = $config->get('azure_service_bus_connector.settings');
    $this->logger = $logger->get('azure_service_bus_connector');
  }

  /**
   * {@inheritdoc}
   *
   * @codeCoverageIgnore
   */
  public static function create(ContainerInterface $container = NULL) {
    if (is_null($container)) {
      $container = \Drupal::getContainer();
    }
    return new static(
      $container->get('config.factory'),
      $container->get('logger.factory')
    );
  }

  /**
   * Set the shared access endpoint.
   */
  public function setSharedAccessEndpoint() {
    $this->sharedAccessEndpoint = $this->config->get('endpoint');
    if (!empty($this->sharedAccessEndpoint)) {
      return $this->sharedAccessEndpoint;
    }

    return FALSE;
  }

  /**
   * Retrieve the shared access endpoint or set it if not already set.
   */
  public function getSharedAccessEndpoint() {
    if (!empty($this->sharedAccessEndpoint)) {
      return $this->sharedAccessEndpoint;
    }
    else {
      return $this->setSharedAccessEndpoint();
    }
  }

  /**
   * Set the shared access key name.
   */
  public function setSharedAccessKeyName() {
    $this->sharedAccessKeyName = $this->config->get('shared_access_key_name');
    if (!empty($this->sharedAccessKeyName)) {
      return $this->sharedAccessKeyName;
    }

    return FALSE;
  }

  /**
   * Retrieve the shared access key name or set it if not already set.
   */
  public function getSharedAccessKeyName() {
    if (!empty($this->sharedAaccessKeyName)) {
      return $this->sharedAaccessKeyName;
    }
    else {
      return $this->setSharedAccessKeyName();
    }
  }

  /**
   * Set the shared access key.
   */
  public function setSharedAccessKey() {
    $this->sharedAccessKey = $this->config->get('shared_access_key');
    if (!empty($this->sharedAccessKey)) {
      return $this->sharedAccessKey;
    }

    return FALSE;
  }

  /**
   * Retrieve the shared access key or set it if not already set.
   */
  public function getSharedAccessKey() {
    if (!empty($this->sharedAccessKey)) {
      return $this->sharedAccessKey;
    }
    else {
      return $this->setSharedAccessKey();
    }
  }

  /**
   * Set the connection string.
   */
  public function setConnectionString() {
    $endpoint = $this->getSharedAccessEndpoint();
    $accessKeyName = $this->getSharedAccessKeyName();
    $accessKey = $this->getSharedAccessKey();

    if ($endpoint && $accessKeyName && $accessKey) {
      return "Endpoint=$endpoint;SharedAccessKeyName=$accessKeyName;SharedAccessKey=$accessKey";
    }
    else {
      if ($endpoint === FALSE) {
        $this->logDebugMessage('Endpoint URL generation failed due to missing endpoint value.');
        return FALSE;
      }
      elseif ($accessKeyName === FALSE) {
        $this->logDebugMessage('Endpoint URL generation failed due to missing access key name value.');
        return FALSE;
      }
      elseif ($accessKey === FALSE) {
        $this->logDebugMessage('Endpoint URL generation failed due to missing access key value.');
        return FALSE;
      }
      else {
        return FALSE;
      }
    }
  }

  /**
   * Get the connection string or set it if not already set.
   */
  public function getConnectionString() {
    if (!empty($this->connectionString)) {
      return $this->connectionString;
    }
    else {
      return $this->setConnectionString();
    }
  }

  /**
   * Get the Service Bus instance.
   */
  public function getServiceBus() {
    $connection = $this->getConnectionString();
    if (!empty($connection)) {
      return ServicesBuilder::getInstance()->createServiceBusService($connection);
    }

    return FALSE;
  }

  /**
   * Add additional logging when debug mode is enabled.
   *
   * @param string $message
   *   The message to log.
   * @param array $params
   *   Any additional values to log, such as a request payload.
   */
  public function logDebugMessage($message, array $params = []) {
    if (!empty($this->config->get('debug_mode'))) {
      if (!empty($params)) {
        $this->logger->log('info', $this->t('@message @params', [
          '@message' => $message,
          '@params' => print_r($params, TRUE),
        ]));
      }
      else {
        $this->logger->log('info', $this->t('@message', [
          '@message' => $message,
        ]));
      }
    }
  }

}
