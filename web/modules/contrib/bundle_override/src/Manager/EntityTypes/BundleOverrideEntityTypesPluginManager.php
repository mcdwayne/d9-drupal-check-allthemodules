<?php

namespace Drupal\bundle_override\Manager\EntityTypes;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Class BundleOverrideEntityTypesPluginManager.
 *
 * @package Drupal\bundle_override\Manager\EntityTypes
 *
 * @author Thomas Sécher
 */
class BundleOverrideEntityTypesPluginManager extends DefaultPluginManager {

  const SERVICE_NAME = 'bundle_override.entity_types_plugin_manager';

  protected $map = [];
  protected $serviceMap = NULL;

  /**
   * Return the service.
   *
   * @return static
   *   The service.
   */
  public static function me() {
    return \Drupal::service(static::SERVICE_NAME);
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/bundle_override/EntityTypes',
      $namespaces,
      $module_handler,
      'Drupal\bundle_override\Manager\EntityTypes\BundleOverrideEntityTypesInterface',
      'Drupal\bundle_override\Manager\EntityTypes\BundleOverrideEntityTypes');

    $this->alterInfo('bundle_override_entity_types_info');
    $this->setCacheBackend($cache_backend, 'bundle_override_entity_types_info');

    // Load the services.
    $this->loadAllServices();
  }

  /**
   * Implements hook_entity_type_alter().
   *
   * Redefine the storage class to use for each entity type according to
   * BundleOverrideEntityType plugin.
   */
  public function alterEntityTypes(array &$entityTypes) {
    foreach ($this->getDefinitions() as $entityTypeId => $options) {
      // Check definition entity type in entity Types.
      if (array_key_exists($entityTypeId, $entityTypes)) {
        $entityType = $entityTypes[$entityTypeId];
        if ($instance = $this->getInstance($options)) {
          $entityType->setStorageClass($instance->getStorageClass());
          $instance->alterEntityType($entityType);
        }
      }
    }
  }

  /**
   * Get the service EntityType Manager by service ID.
   *
   * @param string $serviceId
   *   The service id.
   *
   * @return \Drupal\bundle_override\Manager\EntityTypes\BundleOverrideEntityTypesInterface|null
   *   The entity type.
   *
   * @throws \Exception
   *   Error if no service is found.
   */
  public function getInstanceByServiceId($serviceId) {
    $this->loadAllServices();

    if (array_key_exists($serviceId, $this->serviceMap)) {
      return $this->serviceMap[$serviceId];
    }

    throw new \Exception('Service ' . $serviceId . ' not existing');
  }

  /**
   * {@inheritdoc}
   */
  public function getInstance(array $options) {
    $pluginId = $options['id'];
    if (!array_key_exists($pluginId, $this->map)) {
      $this->map[$pluginId] = $this->createInstance($pluginId, $options);
    }
    return $this->map[$pluginId];
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = []) {
    /** @var BundleOverrideEntityTypesInterface $instance */
    $instance = new $configuration['class']($this->namespaces, $this->cacheBackend, $this->moduleHandler);

    // On crée le service.
    $this->addService($instance);

    return $instance;
  }

  /**
   * Add service to storage.
   *
   * @param \Drupal\bundle_override\Manager\EntityTypes\BundleOverrideEntityTypesInterface $instance
   *   Add the service to the container.
   */
  protected function addService(BundleOverrideEntityTypesInterface $instance) {
    if (!\Drupal::getContainer()->has($instance->getServiceId())) {
      \Drupal::getContainer()->set($instance->getServiceId(), $instance);
    }
  }

  /**
   * Load all services.
   */
  protected function loadAllServices() {
    if (is_null($this->serviceMap)) {
      $this->serviceMap = [];
      foreach ($this->getDefinitions() as $pluginId => $options) {
        if ($instance = $this->getInstance($options)) {
          $this->serviceMap[$instance->getServiceId()] = $instance;
        }
      }
    }
  }

}
