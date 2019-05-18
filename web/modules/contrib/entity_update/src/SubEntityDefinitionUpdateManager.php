<?php

namespace Drupal\entity_update;

use Drupal\Core\Entity\EntityDefinitionUpdateManager;

/**
 * This is an extention of EntityDefinitionUpdateManager.
 */
class SubEntityDefinitionUpdateManager extends EntityDefinitionUpdateManager {

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  public $entityManager;

  /**
   * Gets a list of changes to entity type and field storage definitions.
   *
   * @return array
   *   An associative array keyed by entity type id of change descriptors. Every
   *   entry is an associative array with the following optional keys:
   *   - entity_type: a scalar having only the DEFINITION_UPDATED value.
   *   - field_storage_definitions: an associative array keyed by field name of
   *     scalars having one value among:
   *     - DEFINITION_CREATED
   *     - DEFINITION_UPDATED
   *     - DEFINITION_DELETED
   */
  public function publicGetChangeList() {
    return parent::getChangeList();
  }

  /**
   * Performs a field storage definition update.
   *
   * @param string $op
   *   The operation to perform, possible values are static::DEFINITION_CREATED,
   *   static::DEFINITION_UPDATED or static::DEFINITION_DELETED.
   * @param array|null $storage_definition
   *   The new field storage definition.
   * @param array|null $original_storage_definition
   *   The original field storage definition.
   */
  public function publicDoFieldUpdate($op, $storage_definition = NULL, $original_storage_definition = NULL) {
    return parent::doFieldUpdate($op, $storage_definition, $original_storage_definition);
  }

  /**
   * Performs an entity type definition update.
   *
   * @param string $op
   *   The operation to perform, either static::DEFINITION_CREATED or
   *   static::DEFINITION_UPDATED.
   * @param string $entity_type_id
   *   The entity type ID.
   */
  public function publicDoEntityUpdate($op, $entity_type_id) {
    return parent::doEntityUpdate($op, $entity_type_id);
  }

}
