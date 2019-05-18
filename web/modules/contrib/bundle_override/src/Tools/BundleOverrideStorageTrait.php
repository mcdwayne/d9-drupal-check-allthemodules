<?php

namespace Drupal\bundle_override\Tools;

/**
 * Trait BundleOverrideStorageTrait.
 *
 * @package Drupal\bundle_override\Tools
 */
trait BundleOverrideStorageTrait {

  /**
   * Set the entity class to use when creating / loading.
   *
   * @param string $entity_class
   *   The entity class to use.
   */
  public function setEntityClass($entity_class) {
    $this->entityClass = $entity_class;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildQuery($ids, $revision_ids = FALSE) {
    $query = parent::buildQuery($ids, $revision_ids);

    if (method_exists($this->entityClass, 'getStaticBundle')) {
      if ($bundle = call_user_func($this->entityClass . '::getStaticBundle')) {
        $query->condition($this->getEntityType()->getKey('bundle'), $bundle);
      }
    }

    return $query;
  }

  /**
   * Creates an entity from the bundle override plugin Manager class name.
   *
   * @param string $pluginManagerClassName
   *   The plugin manage class name.
   * @param array $values
   *   The values of the entity.
   *
   * @return mixed
   *   The entity.
   */
  public function createFromPluginManagerClassName($pluginManagerClassName, array $values = []) {
    $base_entity_class = $this->entityClass;
    $this->entityClass = $pluginManagerClassName::me()
      ->getClassByBundle($values[$this->getEntityType()
        ->getKey('bundle')]) ?: $this->entityClass;
    $result = parent::create($values);
    $this->entityClass = $base_entity_class;
    return $result;
  }

}
