<?php

namespace Drupal\core_extend\Entity\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\FieldableEntityInterface;

/**
 * Provides a trait for determining entity type ID key type.
 *
 * @see \Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider
 */
trait EntityTypeIdKeyTypeTrait {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Gets the entity field manager.
   *
   * @return \Drupal\Core\Entity\EntityFieldManagerInterface
   *   The entity field manager.
   */
  protected function getEntityFieldManager() {
    if (is_null($this->entityFieldManager)) {
      $this->entityFieldManager = \Drupal::service('entity_field.manager');
    }
    return $this->entityFieldManager;
  }

  /**
   * Gets the type of the ID key for a given entity type.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   An entity type.
   *
   * @return string|null
   *   The type of the ID key for a given entity type, or NULL if the entity
   *   type does not support fields.
   */
  protected function getEntityTypeIdKeyType(EntityTypeInterface $entity_type) {
    if (!$entity_type->entityClassImplements(FieldableEntityInterface::class)) {
      return NULL;
    }

    $field_storage_definitions = $this->getEntityFieldManager()->getFieldStorageDefinitions($entity_type->id());
    return $field_storage_definitions[$entity_type->getKey('id')]->getType();
  }

}
