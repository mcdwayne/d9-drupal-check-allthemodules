<?php

namespace Drupal\apitools;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Http\ClientFactory;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\TempStore\SharedTempStoreFactory;
use Drupal\key\KeyRepositoryInterface;
use GuzzleHttp\HandlerStack;

/**
 * Provides the Client annotation plugin manager.
 */
class ClientManager extends DefaultPluginManager implements ClientManagerInterface {

  /**
   * @var \GuzzleHttp\HandlerStack
   */
  protected $http;

  /**
   * @var \Drupal\Core\Http\ClientFactory
   */
  protected $clientFactory;

  /**
   * @var ModelManagerInterface
   */
  protected $modelManager;

  /**
   * @var KeyRepositoryInterface
   */
  protected $keyRepository;

  /**
   * @var \Drupal\Core\TempStore\SharedTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  protected $clients = [];

  /**
   * Constructs a new ClientManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, HandlerStack $handler_stack, ClientFactory $client_factory, ModelManagerInterface $model_manager, KeyRepositoryInterface $key_repository, SharedTempStoreFactory $temp_store_factory, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct('Plugin/ApiTools', $namespaces, $module_handler, 'Drupal\apitools\ClientInterface', 'Drupal\apitools\Annotation\ApiToolsClient');

    $this->alterInfo('apitools_apitools_client_info');
    $this->setCacheBackend($cache_backend, 'apitools_apitools_client_plugins');

    $this->http = $handler_stack;
    $this->modelManager = $model_manager;
    $this->clientFactory = $client_factory;
    $this->keyRepository = $key_repository;
    $this->tempStoreFactory = $temp_store_factory;
    $this->entityTypeManager = $entity_type_manager;
  }

  public function getDefinitions() {
    return parent::getDefinitions();
  }

  /**
   * @return ModelManagerInterface
   */
  public function getModelManager() {
    return $this->modelManager;
  }

  public function getClientFactory() {
    return $this->clientFactory;
  }

  public function getTempStore($id) {
    return $this->tempStoreFactory->get($id . '_oauth_tokens');
  }

  public function getKeyValueFromConfig($key_name, $config_name) {
    $config = \Drupal::config($config_name);
    if (!$key = $config->get($key_name)) {
      return FALSE;
    }
    $key_manager = \Drupal::service('key.repository');
    if (!$key_entity = $key_manager->getKey($key)) {
      return FALSE;
    }
    return $key_entity->getKeyValue();
  }

  public function load($id, array $options = []) {
    if (isset($this->clients[$id])) {
      return $this->clients[$id];
    }
    $this->clients[$id] = FALSE;
    try {
      $this->clients[$id] = $this->createInstance($id)->init($options);
    }
    catch (\Exception $e) {
      watchdog_exception('apitools', $e);
    }
    return $this->clients[$id];
  }
}
