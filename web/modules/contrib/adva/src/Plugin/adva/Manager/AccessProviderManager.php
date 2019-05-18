<?php

namespace Drupal\adva\Plugin\adva\Manager;

use Drupal\adva\Plugin\adva\AccessConsumerInterface;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Defines interface for AccessProviderManager.
 *
 * @see \Drupal\adva\Annotation\AccessProvider.
 */
class AccessProviderManager extends DefaultPluginManager implements AccessProviderManagerInterface {

  /**
   * Constructs a AccessConsumerManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/adva/AccessProvider',
      $namespaces,
      $module_handler,
      'Drupal\adva\Plugin\adva\AccessProviderInterface',
      'Drupal\adva\Annotation\AccessProvider'
    );
    $this->alterInfo('adva_provider');
    $this->setCacheBackend($cache_backend, 'adva_provider_plugins');
    $this->factory = new AccessProviderFactory($this, $this->pluginInterface);
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableProvidersForEntityType(EntityTypeInterface $entityType) {
    $availableProviders = &drupal_static(__FUNCTION__);
    if (!isset($availableProviders[$entityType->id()])) {
      // Construct list of available providers for the entity type.
      $available = [];
      $factory = $this->getFactory();
      foreach ($this->getDefinitions() as $definition_id => $definition) {
        $provider_class = $factory::getPluginClass($definition_id, $definition);
        if ($provider_class::appliesToType($entityType)) {
          $available[$definition_id] = $definition;
        }
      }
      $availableProviders[$entityType->id()] = $available;
    }
    return $availableProviders[$entityType->id()];
  }

  /**
   * {@inheritdoc}
   */
  public function getProviders(AccessConsumerInterface $consumer) {
    $definitions = $this->getDefinitions();
    $providers = [];
    foreach ($consumer->getAccessProviderIds() as $pid) {
      $definition = $definitions[$pid];
      if (!$definition) {
        continue;
      }
      $config = $consumer->getAccessProviderConfig($pid);
      $instance = $this->createInstance($pid, $config, $consumer);
      $providers[$pid] = $instance;
    }
    return $providers;
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = [], AccessConsumerInterface $consumer = NULL) {
    return $this->getFactory()->createInstance($plugin_id, $configuration, $consumer);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitionClass($definition_id, array $definition) {
    return AccessProviderFactory::getPluginClass($definition_id, $definition);
  }

}
