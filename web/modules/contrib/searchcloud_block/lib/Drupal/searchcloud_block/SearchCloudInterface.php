<?php

namespace Drupal\searchcloud_block;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;

interface SearchCloudInterface extends EntityInterface {

  /**
   * Defines the base fields of the entity type.
   *
   * @param EntityTypeInterface $entity_type
   *   Name of the entity type
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   *   An array of entity field definitions, keyed by field name.
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type);

  /**
   * Returns the identifier.
   *
   * @return int
   *   The entity identifier.
   */
  public function id();

  /**
   * Returns the entity UUID (Universally Unique Identifier).
   *
   * The UUID is guaranteed to be unique and can be used to identify an entity
   * across multiple systems.
   *
   * @return string
   *   The UUID of the entity.
   */
  public function uuid();

}
