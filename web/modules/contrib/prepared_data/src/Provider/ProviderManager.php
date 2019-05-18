<?php

namespace Drupal\prepared_data\Provider;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\prepared_data\Builder\DataBuilderInterface;
use Drupal\prepared_data\Storage\StorageInterface;

/**
 * The manager class for data provider plugins.
 */
class ProviderManager extends DefaultPluginManager {

  /**
   * The storage of prepared data.
   *
   * @var \Drupal\prepared_data\Storage\StorageInterface
   */
  protected $dataStorage;

  /**
   * The builder which builds up and refreshes prepared data.
   *
   * @var \Drupal\prepared_data\Builder\DataBuilderInterface
   */
  protected $dataBuilder;

  /**
   * The account associated as current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * A list of instantiated providers.
   *
   * @var \Drupal\prepared_data\Provider\ProviderInterface[]
   */
  protected $providers;

  /**
   * A sorted list of instantiated providers.
   *
   * @var \Drupal\prepared_data\Provider\ProviderInterface[]
   */
  protected $sortedProviders;

  /**
   * ProviderManager constructor.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param CacheBackendInterface $cache_backend
   *   The cache backend.
   * @param ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\prepared_data\Storage\StorageInterface $data_storage
   *   The storage of prepared data.
   * @param \Drupal\prepared_data\Builder\DataBuilderInterface $data_builder
   *   The builder which builds up and refreshes prepared data.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The account to be used as current user.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, StorageInterface $data_storage, DataBuilderInterface $data_builder, AccountInterface $current_user) {
    parent::__construct('Plugin/prepared_data/Provider', $namespaces, $module_handler, 'Drupal\prepared_data\Provider\ProviderInterface', 'Drupal\prepared_data\Annotation\PreparedDataProvider');
    $this->alterInfo('prepared_data_provider_info');
    $this->setCacheBackend($cache_backend, 'prepared_data_provider');
    $this->dataStorage = $data_storage;
    $this->dataBuilder = $data_builder;
    $this->currentUser = $current_user;
  }

  /**
   * Returns a list of all provider instances, sorted by their priority.
   *
   * @return \Drupal\prepared_data\Provider\ProviderInterface[]
   *   The sorted list of available provider instances.
   */
  public function getAllProviders() {
    if (!isset($this->sortedProviders)) {
      $providers = [];
      foreach ($this->getDefinitions() as $definition) {
        $priority = (int) $definition['priority'];
        $i = 0;
        while (TRUE) {
          $i++;
          $priority += ($i / 100);
          if (!isset($providers[$priority])) {
            $providers[$priority] = $this->createInstance($definition['id']);
            break;
          }
        }
      }
      ksort($providers);

      $this->sortedProviders = array_values($providers);
    }

    return $this->sortedProviders;
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = []) {
    if (!isset($this->providers[$plugin_id])) {
      /** @var \Drupal\prepared_data\Provider\ProviderInterface $instance */
      $instance = parent::createInstance($plugin_id, $configuration);
      $instance->setDataStorage($this->dataStorage);
      $instance->setDataBuilder($this->dataBuilder);
      $instance->setCurrentUser($this->currentUser);
      $this->providers[$plugin_id] = $instance;
    }
    return $this->providers[$plugin_id];
  }

}
