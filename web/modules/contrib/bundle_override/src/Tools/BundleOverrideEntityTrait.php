<?php

namespace Drupal\bundle_override\Tools;

/**
 * Trait BundleOverrideStorageTrait.
 *
 * @package Drupal\bundle_override\Tools
 */
trait BundleOverrideEntityTrait {

  /**
   * {@inheritdoc}
   *
   * @return static
   */
  public static function create(array $values = []) {
    $values += [
      static::getOverridedStorage()
        ->getEntityType()
        ->getKey('bundle') => static::getStaticBundle()
    ];
    return static::getOverridedStorage()->create($values);
  }

  /**
   * {@inheritdoc}
   *
   * @return static[]
   *   The list of entities.
   */
  public static function loadMultiple(array $ids = NULL) {
    return static::getOverridedStorage()->loadMultiple($ids);
  }

  /**
   * Returns the object that overrides the default entity storage class.
   *
   * @param string $className
   *   The overriden storage class name.
   *
   * @return mixed
   *   The storage object.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected static function getOverridedStorageFromClassName($className) {
    $entity_type = \Drupal::entityTypeManager()->getDefinition(static::getStaticEntityTypeId());
    $storage = $className::createInstance(\Drupal::getContainer(), $entity_type);
    $storage->setEntityClass(get_called_class());
    return $storage;
  }

}
