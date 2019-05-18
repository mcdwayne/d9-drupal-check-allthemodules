<?php

namespace Drupal\bundle_override\Manager\Objects;

use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\bundle_override\Manager\EntityTypes\BundleOverrideEntityTypesPluginManager;
use Drupal\bundle_override\Manager\EntityTypes\BundleOverrideEntityTypesInterface;

/**
 * Class AbstractBundleOverrideObjectsPluginManager.
 *
 * Abstraction des managers.
 *
 * @package Drupal\bundle_override\Manager\Objects
 */
abstract class AbstractBundleOverrideObjectsPluginManager extends DefaultPluginManager implements BundleOverrideEntityTypesInterface {

  /**
   * The service name.
   */
  const SERVICE_NAME = '';

  /**
   * The entity type id.
   */
  const ENTITY_TYPE_ID = '';

  /**
   * The map of Objects.
   *
   * @var array
   */
  protected $map = FALSE;

  /**
   * The default class.
   *
   * @var string
   */
  private $defaultClass;

  /**
   * The redefiner class.
   *
   * @var string
   */
  private $redefinerClass;

  /**
   * Return the default entity class name.
   *
   * @return string
   *   The default class name.
   */
  abstract public function getDefaultEntityClass();

  /**
   * Return the re-definer class name.
   *
   * @return string
   *   The re-definer class name.
   */
  abstract public function getRedefinerClass();

  /**
   * Return the service.
   *
   * @return static
   *   The service.
   *
   * @throws \Exception
   */
  public static function me() {
    try {
      $service = \Drupal::service(static::SERVICE_NAME);
    }
    catch (\Exception $e) {
      $service = BundleOverrideEntityTypesPluginManager::me()
        ->getInstanceByServiceId(static::SERVICE_NAME);
    }

    return $service;
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/bundle_override/Objects/' . static::ENTITY_TYPE_ID,
      $namespaces,
      $module_handler,
      'Drupal\bundle_override\Manager\Objects\BundleOverrideObjectsInterface',
      'Drupal\bundle_override\Manager\Objects\BundleOverrideObjects');

    $this->alterInfo('bundle_override_objects_' . static::ENTITY_TYPE_ID . '_info');
    $this->setCacheBackend($cache_backend, 'bundle_override_objects_' . static::ENTITY_TYPE_ID . '_info');

    // Init the redefiner class.
    $this->redefinerClass = $this->getRedefinerClass();
    $this->defaultClass = $this->getDefaultEntityClass();

    // Check the validity of the redefinerClass.
    if (!is_subclass_of($this->redefinerClass, $this->defaultClass)) {
      throw new \Exception($this->redefinerClass . ' needs to be a subclass of ' . $this->defaultClass);
    }
  }

  /**
   * Return the class of the entity for the asked bundle.
   *
   * @param string $bundle
   *   The bundle.
   *
   * @return string|null
   *   The object class.
   */
  public function getClassByBundle($bundle) {
    // Initialize the objects map.
    if ($this->map === FALSE) {
      $this->map = [];
      foreach ($this->getDefinitions() as $pluginId => $definition) {
        $class = str_replace('\\\\', '\\', $definition['class']);
        if ($this->isElligibleClass($class)) {
          $this->map[$pluginId] = $class;
        }
      }
    }

    if (array_key_exists($bundle, $this->map)) {
      return $this->map[$bundle];
    }
    return NULL;
  }

  /**
   * Get the entity from the storage data.
   *
   * @param array $entity_values
   *   The entity values.
   * @param string $entityTypeId
   *   The entity type id.
   * @param string $bundle
   *   The entity bundle.
   * @param array $array_keys
   *   The array keys.
   *
   * @return EntityInterface
   *   The default entity.
   */
  public function getEntityByStorageData(array $entity_values, $entityTypeId, $bundle, array $array_keys) {
    $objectClass = $this->getClassByBundle($bundle);
    if ($objectClass) {
      return new $objectClass($entity_values, $entityTypeId, $bundle, $array_keys);
    }

    // Default process.
    return $this->getDefaultEntityFromStorageData($entity_values, $entityTypeId, $bundle, $array_keys);
  }

  /**
   * Check if the class is elligible.
   *
   * @return bool
   *   The elligibility.
   */
  protected function isElligibleClass($className) {
    return is_subclass_of($className, $this->redefinerClass);
  }

  /**
   * Get the entity from the storage data.
   *
   * @param array $entity_values
   *   The entity values.
   * @param string $entityTypeId
   *   The entity type id.
   * @param string $bundle
   *   The entity bundle.
   * @param array $array_keys
   *   The array keys.
   *
   * @return EntityInterface
   *   The default entity.
   */
  protected function getDefaultEntityFromStorageData($entity_values, $entityTypeId, $bundle, $array_keys) {
    return new $this->defaultClass($entity_values, $entityTypeId, $bundle, $array_keys);
  }

  /**
   * Allow Entity Type altering.
   *
   * @param \Drupal\Core\Entity\ContentEntityType $entityType
   *   The entity type.
   */
  public function alterEntityType(ContentEntityType $entityType) {
    // Mute.
  }

}
