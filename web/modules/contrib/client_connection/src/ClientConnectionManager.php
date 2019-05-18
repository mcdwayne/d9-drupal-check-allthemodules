<?php

namespace Drupal\client_connection;

use Drupal\Component\Plugin\CategorizingPluginManagerInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\CategorizingPluginManagerTrait;
use Drupal\Core\Plugin\Context\ContextAwarePluginManagerInterface;
use Drupal\Core\Plugin\Context\ContextAwarePluginManagerTrait;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\CurrentRouteMatch;

/**
 * Provides the Client Connection plugin manager.
 */
class ClientConnectionManager extends DefaultPluginManager implements ContextAwarePluginManagerInterface, CategorizingPluginManagerInterface {

  use CategorizingPluginManagerTrait {
    getSortedDefinitions as traitGetSortedDefinitions;
    getGroupedDefinitions as traitGetGroupedDefinitions;
  }
  use ContextAwarePluginManagerTrait;

  /**
   * The Client Connection cache backend to store resolved instances.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheFactory;

  /**
   * The class resolver.
   *
   * @var \Drupal\Core\DependencyInjection\ClassResolverInterface
   */
  protected $classResolver;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * An array of service ids.
   *
   * @var string[]
   */
  protected $serviceIds;

  /**
   * The Client Connection Config entity storage.
   *
   * @var \Drupal\client_connection\Entity\Storage\ClientConnectionConfigStorageInterface
   */
  protected $storage;

  /**
   * Constructs a new ClientConnectionManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_factory
   *   The Client Connection cache backend to store resolved instances.
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $class_resolver
   *   The class resolver.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param string[] $service_ids
   *   An array of service IDs in order.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, CacheBackendInterface $cache_factory, ClassResolverInterface $class_resolver, EntityTypeManagerInterface $entity_type_manager, array $service_ids) {
    parent::__construct('Plugin/ClientConnection', $namespaces, $module_handler, 'Drupal\client_connection\Plugin\ClientConnection\ClientConnectionInterface', 'Drupal\client_connection\Annotation\ClientConnection');

    $this->alterInfo('client_connection_info');
    $this->setCacheBackend($cache_backend, 'client_connection_plugins');

    $this->cacheFactory = $cache_factory;
    $this->classResolver = $class_resolver;
    $this->entityTypeManager = $entity_type_manager;
    $this->serviceIds = $service_ids;
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);
    $this->processDefinitionCategory($definition);

    $definition['context']['client_connection_config'] = new ContextDefinition('entity:client_connection_config', $this->t('Client Connection Configuration'));
  }

  /**
   * {@inheritdoc}
   */
  public function getSortedDefinitions(array $definitions = NULL) {
    // Sort the plugins first by category, then by label.
    return $this->traitGetSortedDefinitions($definitions, 'label');
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupedDefinitions(array $definitions = NULL) {
    return $this->traitGetGroupedDefinitions($definitions, 'label');
  }

  /**
   * {@inheritdoc}
   */
  public function getInstance(array $options) {
    $options += [
      'plugin' => NULL,
      'contexts' => [],
      'channel' => 'site',
    ];

    if ($this->entityTypeManager->getDefinition('client_connection_config', FALSE)) {
      return $this->resolveInstance($options['plugin'], $options['contexts'], $options['channel']);
    }

    return parent::getInstance($options);
  }

  /**
   * Generates an instance's cache key for the given contexts.
   *
   * @param string $plugin_id
   *   The plugin ID to resolve configuration for.
   * @param \Drupal\Core\Plugin\Context\ContextInterface[] $contexts
   *   Available contextual information to help resolve the configuration.
   * @param string $channel_id
   *   The channel ID. Channels allow to segregate different areas of
   *   configuration, like returning user-specific vs site-wide configuration.
   *
   * @return string|null
   *   The client connection cache key.
   */
  protected function getInstanceCacheKey($plugin_id, array $contexts = [], $channel_id = 'site') {
    $keys = [
      $channel_id,
      $plugin_id,
    ];

    $context_ids = [];
    foreach ($contexts as $context_id => $context) {
      $value = $context->getContextValue();
      if (is_object($value)) {
        if ($value instanceof EntityInterface) {
          $context_ids[$context_id] = $value->uuid();
        }
        elseif ($value instanceof CurrentRouteMatch) {
          $context_ids[$context_id] = $value->getRouteObject()->serialize();
        }
        elseif (method_exists($value, 'uuid')) {
          $context_ids[$context_id] = $value->uuid();
        }
        elseif (method_exists($value, 'id')) {
          $context_ids[$context_id] = $value->id();
        }
        elseif (method_exists($value, 'toString')) {
          $context_ids[$context_id] = $value->toString();
        }
        elseif (method_exists($value, 'serialize')) {
          $context_ids[$context_id] = $value->serialize();
        }
      }
      elseif (is_string($value) || is_int($value) || is_float($value) || is_bool($value)) {
        $context_ids[$context_id] = $value;
      }
    }

    // @todo add cache contexts from each context

    ksort($context_ids);
    $keys[] = hash_hmac('crc32', serialize($context_ids), 1);

    return implode(':', $keys);
  }

  /**
   * Gets the client connection config entity storage.
   *
   * @return \Drupal\client_connection\Entity\Storage\ClientConnectionConfigStorageInterface
   *   The client connection config storage instance.
   */
  protected function getStorage() {
    if (is_null($this->storage)) {
      $this->storage = $this->entityTypeManager->getStorage('client_connection_config');
    }
    return $this->storage;
  }

  /**
   * Resolves the connection configuration entity id.
   *
   * @param string $plugin_id
   *   The plugin ID to resolve configuration for.
   * @param \Drupal\Core\Plugin\Context\ContextInterface[] $contexts
   *   Available contextual information to help resolve the configuration.
   * @param string $channel_id
   *   The channel ID. Channels allow to segregate different areas of
   *   configuration, like returning user-specific vs site-wide configuration.
   * @param bool $use_cache
   *   Load from cache if cache is set for this context.
   *
   * @return string|null
   *   The client connection configuration id, if resolved. Otherwise NULL,
   *   indicating that the next resolver in the chain should be called.
   */
  public function resolveId($plugin_id, array $contexts = [], $channel_id = 'site', $use_cache = TRUE) {
    // @todo add alter here to modify contexts
    // $this->moduleHandler->alter()
    // Load from cache.
    $cache_id = $this->getInstanceCacheKey($plugin_id, $contexts, $channel_id);
    if ($use_cache && $cache = $this->cacheFactory->get($cache_id)) {
      return $cache->data['id'];
    }

    // Resolve ID.
    $client_id = NULL;
    foreach ($this->serviceIds as $service_id) {
      /** @var \Drupal\client_connection\Resolver\ConnectionResolverInterface $resolver */
      $resolver = $this->classResolver->getInstanceFromDefinition($service_id);
      if ($resolver->applies($plugin_id, $contexts, $channel_id)) {
        $result = $resolver->resolve($plugin_id, $contexts, $channel_id);
        if (is_string($result) || is_int($result)) {
          $client_id = $result;
          break;
        }
      }
    }

    // Build caching information.
    $cache_tags[] = 'client_connection_config:' . $client_id;
    $cache_max_age = Cache::PERMANENT;
    foreach ($contexts as $context) {
      $cache_tags = Cache::mergeTags($cache_tags, $context->getCacheTags());
      $cache_max_age = Cache::mergeMaxAges($cache_max_age, $context->getCacheMaxAge());
    }

    $this->cacheFactory->set($cache_id, ['id' => $client_id], $cache_max_age, $cache_tags);

    return $client_id;
  }

  /**
   * Resolves the connection plugin.
   *
   * @param string $plugin_id
   *   The plugin ID to resolve configuration for.
   * @param \Drupal\Core\Plugin\Context\ContextInterface[] $contexts
   *   Available contextual information to help resolve the configuration.
   * @param string $channel_id
   *   The channel ID. Channels allow to segregate different areas of
   *   configuration, like returning user-specific vs site-wide configuration.
   * @param bool $use_cache
   *   Load from cache if cache is set for this context.
   *
   * @return \Drupal\client_connection\Plugin\ClientConnection\ClientConnectionInterface|null
   *   The client connection configuration plugin, if resolved.
   */
  public function resolveInstance($plugin_id, array $contexts = [], $channel_id = 'site', $use_cache = TRUE) {
    /** @var \Drupal\client_connection\Entity\ClientConnectionConfigInterface $entity */
    $id = $this->resolveId($plugin_id, $contexts, $channel_id, $use_cache);
    if (!is_null($id) && $this->getStorage() && $entity = $this->getStorage()->load($id)) {
      return $entity->getPlugin();
    }
    return NULL;
  }

  /**
   * Finds a Client Connection Configuration entity ID.
   *
   * @param string $plugin_id
   *   The plugin ID.
   * @param string $instance_id
   *   The instance ID to retrieve. This allows for multiple instances of
   *   configuration in the same channel.
   * @param string $channel_id
   *   The channel ID. Channels allow to segregate different areas of
   *   configuration, like returning user-specific vs site-wide configuration.
   *
   * @return null|string
   *   The client connection entity ID if found. Null otherwise.
   */
  protected function findConfigId($plugin_id, $instance_id = 'default', $channel_id = 'site') {
    return $this->getStorage()->findId($plugin_id, $instance_id, $channel_id);
  }

  /**
   * Loads a Client Connection Configuration entity.
   *
   * Only use this directly to specifically load a configuration instance. Use
   * ClientConnectionManager::getConfigInstance() to allow configuration to be
   * loaded based on passed-in context.
   *
   * @param string $plugin_id
   *   The plugin ID.
   * @param string $instance_id
   *   The instance ID to retrieve. This allows for multiple instances of
   *   configuration in the same channel.
   * @param string $channel_id
   *   The channel ID. Channels allow to segregate different areas of
   *   configuration, like returning user-specific vs site-wide configuration.
   *
   * @return \Drupal\client_connection\Entity\ClientConnectionConfigInterface|null
   *   The client connection entity if found. Null otherwise.
   */
  protected function loadConfig($plugin_id, $instance_id = 'default', $channel_id = 'site') {
    if ($id = $this->findConfigId($plugin_id, $instance_id, $channel_id)) {
      return $this->getStorage()->load($id);
    }
    return NULL;
  }

  /**
   * Loads a Client Connection Configuration entity.
   *
   * @param string $plugin_id
   *   The plugin ID.
   * @param string $instance_id
   *   The instance ID to retrieve. This allows for multiple instances of
   *   configuration in the same channel.
   * @param string $channel_id
   *   The channel ID. Channels allow to segregate different areas of
   *   configuration, like returning user-specific vs site-wide configuration.
   *
   * @return \Drupal\client_connection\Plugin\ClientConnection\ClientConnectionInterface|null
   *   The Client Connection plugin if found. Null otherwise.
   */
  public function loadConfigPlugin($plugin_id, $instance_id = 'default', $channel_id = 'site') {
    if ($entity = $this->loadConfig($plugin_id, $instance_id, $channel_id)) {
      return $entity->getPlugin();
    }
    return NULL;
  }

}
