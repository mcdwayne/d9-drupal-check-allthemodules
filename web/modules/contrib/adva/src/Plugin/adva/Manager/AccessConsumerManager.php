<?php

namespace Drupal\adva\Plugin\adva\Manager;

use Drupal\adva\Plugin\adva\OverridingAccessConsumerInterface;
use Drupal\adva\Plugin\adva\AccessProviderInterface;
use Drupal\adva\Entity\AccessConsumer as AccessConsumerConfig;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Defines interface for AccessConsumerManager.
 *
 * @see \Drupal\adva\Annotation\AccessConsumer.
 */
class AccessConsumerManager extends DefaultPluginManager implements AccessConsumerManagerInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Entity Type handler to get entity storage.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct(
      'Plugin/adva/AccessConsumer',
      $namespaces,
      $module_handler,
      'Drupal\adva\Plugin\adva\AccessConsumerInterface',
      'Drupal\adva\Annotation\AccessConsumer'
    );
    $this->entityTypeManager = $entity_type_manager;
    $this->alterInfo('advanced_access_consummer');
    $this->setCacheBackend($cache_backend, 'advanced_access_consummer_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function getConsumerStorage() {
    return $this->entityTypeManager->getStorage("access_consumer");
  }

  /**
   * {@inheritdoc}
   */
  public function getConsumers() {
    static $consumers = NULL;
    if ($consumers === NULL) {
      $definitions = $this->getDefinitions();
      $consumers = [];

      foreach ($definitions as $definition) {
        $config = [
          "settings" => [],
          "providers" => [],
          "provider_config" => [],
        ];
        $configEntity = $this->getConsumerStorage()->load($definition["id"]);
        if ($configEntity) {
          $config["settings"] = $configEntity->getSettings();
          $config["providers"] = $configEntity->getProviders();
          $config["provider_config"] = $configEntity->getAllProviderConfig();
        }
        $instance = $this->createInstance($definition["id"], $config);
        $consumers[$definition["id"]] = $instance;
      }
    }
    return $consumers;
  }

  /**
   * {@inheritdoc}
   */
  public function getConsumer($id) {
    return $this->getConsumers()[$id];
  }

  /**
   * {@inheritdoc}
   */
  public function getConsumerForEntityTypeId($entityTypeId) {
    static $consumersById = [];
    if (isset($consumersById[$entityTypeId])) {
      return $consumersById[$entityTypeId];
    }

    $consumers = $this->getConsumers();
    foreach ($consumers as $consumer) {
      if ($consumer->getEntityTypeId() === $entityTypeId) {
        $consumersById[$entityTypeId] = $consumer;
        return $consumer;
      }
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getConsumerForEntityType(EntityTypeInterface $entityType) {
    return $this->getConsumerForEntityTypeId($entityType->id());
  }

  /**
   * {@inheritdoc}
   */
  public function entityTypeHasConsumer($entityTypeId) {
    $consumer = $this->getConsumerForEntityTypeId($entityTypeId);
    return !empty($consumer);
  }

  /**
   * {@inheritdoc}
   */
  public function getConsumersForProviderId($providerId) {
    static $providerMapping = [];
    if (!isset($providerMapping[$providerId])) {
      $consumers = $this->getConsumers();
      $list = [];
      foreach ($consumers as $consumer) {
        if (in_array($providerId, $consumer->getAccessProviders())) {
          $list[] = $consumer;
        }
      }
      $providerMapping[$providerId] = $list;
    }
    return $providerMapping[$providerId];
  }

  /**
   * {@inheritdoc}
   */
  public function getConsumersForProvider(AccessProviderInterface $provider) {
    return $this->getConsumersForProviderId($provider->id());
  }

  /**
   * {@inheritdoc}
   */
  public function getProviders($entityTypeId) {
    $consumer = $this->getConsumer($entityTypeId);
    if (!$consumer) {
      throw new PluginNotFoundException($entityTypeId);
    }
    return $consumer->getAccessProviders();
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeProviders(EntityTypeInterface $entityType) {
    return $this->getProviders($entityType->id());
  }

  /**
   * {@inheritdoc}
   */
  public function getOverrideConsumers() {
    static $overridingConsumers = NULL;
    if ($overridingConsumers === NULL) {
      $consumers = $this->getConsumers();
      $overridingConsumers = [];
      foreach ($consumers as $consumer) {
        if ($consumer instanceof OverridingAccessConsumerInterface) {
          $overridingConsumers[] = $consumer;
        }
      }
    }
    return $overridingConsumers;
  }

  /**
   * {@inheritdoc}
   */
  public function hasOverrideConsumer($entityTypeId) {
    $overridingConsumers = $this->getOverrideConsumers();
    return isset($overridingConsumers[$entityTypeId]);
  }

  /**
   * {@inheritdoc}
   */
  public function saveConsumers() {
    foreach ($this->getConsumers() as $consumer) {
      $configuration = $consumer->getConfiguration();
      $configEntity = $this->getConsumerStorage()->load($consumer->getEntityTypeId());
      if (!$configEntity) {
        // Create config entity if it does not exist.
        $configEntity = AccessConsumerConfig::create([
          "id" => $consumer->getPluginId(),
        ]);
      }

      $original_settings = $configEntity->getSettings();
      $original_providers = $configEntity->getProviders();
      $original_config = $configEntity->getAllProviderConfig();

      $configEntity->setSettings($configuration["settings"]);
      $configEntity->setProviders($configuration["providers"]);
      foreach ($consumer->getAccessProviders() as $provider_id => $provider) {
        $configEntity->setProviderConfig($provider_id, $provider->getConfiguration());
      }

      $changed_settings = ($original_settings != $configEntity->getSettings());
      $changed_providers = ($original_providers != $configEntity->getProviders());
      $changed_config = ($original_config != $configEntity->getAllProviderConfig());

      if ($changed_settings || $changed_providers || $changed_config) {
        // Only save config entity if there were changes to the entity.
        $configEntity->save();
        $consumer->onChange($configEntity);
      }
    }
  }

}
