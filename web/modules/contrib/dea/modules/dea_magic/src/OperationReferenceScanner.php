<?php

namespace Drupal\dea_magic;

use Doctrine\Entity;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\dea\Plugin\Field\FieldType\EntityOperationItemList;
use Drupal\field\Plugin\migrate\source\d7\Field;

/**
 * Service for scanning an entity for references containing
 * operation fields that specify specific operations.
 */
class OperationReferenceScanner {
  /**
   * @var EntityFieldManagerInterface
   */
  protected $fieldManager;

  /**
   * Create the global instance of the field reference scanner.
   *
   * @param EntityFieldManagerInterface $field_manager
   *   The field manager to use.
   */
  public function __construct(EntityFieldManagerInterface $field_manager) {
    $this->fieldManager = $field_manager;
  }

  /**
   * @param EntityInterface $entity
   * @return FieldDefinitionInterface[]
   */
  public function operationReferenceFields(EntityInterface $entity) {
    if (!($entity instanceof FieldableEntityInterface)) {
      return [];
    }
    $entity_type = $entity->getEntityTypeId();
    $bundle = $entity->bundle();

    $operation_fields = $this->fieldManager->getFieldMapByFieldType('entity_operation');

    $fields = [];

    foreach ($this->fieldManager->getFieldDefinitions($entity_type, $bundle) as $key => $definition) {
      if ($definition->getType() != 'entity_reference') {
        continue;
      }

      $handler_settings = $definition->getSetting('handler_settings');
      if (!$handler_settings) {
        continue;
      }

      // Entity reference fields can use different handlers
      if (array_key_exists('target_bundles', $handler_settings)) {
        $target_bundles = $handler_settings['target_bundles'];
      } elseif (array_key_exists('view', $handler_settings) && array_key_exists('auto_create_bundle', $handler_settings)) {
        // If the field is configured to use the View handler, use the auto_create_bundle property
        $target_bundles = array($handler_settings['auto_create_bundle'] => $handler_settings['auto_create_bundle']);
      } else {
       continue;
      }

      $target_type = $definition->getFieldStorageDefinition()->getSetting('target_type');

      if (!array_key_exists($target_type, $operation_fields)) {
        continue;
      }

      $candidate = FALSE;
      foreach ($operation_fields[$target_type] as $field_name => $info) {
        $candidate = $candidate || count(array_intersect($info['bundles'], $target_bundles)) > 0;
      }

      if (!$candidate) {
        continue;
      }
      $fields[] = $definition;
    }

    return $fields;
  }

  /**
   * @param EntityInterface $entity
   * @return FieldDefinitionInterface[]
   */
  public function operationFields(EntityInterface $entity) {
    if (!($entity instanceof FieldableEntityInterface)) {
      return [];
    }

    $entity_type = $entity->getEntityTypeId();
    $bundle = $entity->bundle();

    $fields = [];

    foreach ($this->fieldManager->getFieldDefinitions($entity_type, $bundle) as $key => $definition) {
      if ($definition->getType() == 'entity_operation') {
        $fields[] = $definition;
      }
    }

    return $fields;
  }

  /**
   * @param EntityInterface $entity
   * @param EntityInterface $target
   * @param string $operation
   *
   * @return EntityInterface[]
   */
  public function operationReferences(EntityInterface $entity, EntityInterface $target = NULL, $operation = NULL) {
    $entities = [];

    foreach ($this->operationReferenceFields($entity) as $field) {
      foreach ($entity->{$field->getName()} as $item) {
        if ($reference = $item->entity) {
          if (is_null($target) || is_null($operation)) {
            $entities[] = $reference;
          }
          else if ($this->providesGrant($reference, $target, $operation)) {
            $entities[] = $reference;
          }
        }
      }
    }
    return $entities;
  }

  public function providesGrant(EntityInterface $reference, EntityInterface $target, $operation) {
    $match = FALSE;
    foreach ($this->operationFields($reference) as $field) {
      $items = $reference->{$field->getName()};
      if ($items instanceof EntityOperationItemList) {
        $match = $items->grants($target, $operation);
      }
    }
    return $match;
  }

  /**
   * @param EntityInterface $entity
   *   The entity to scan for references.
   * @param string $operation
   *   The operation to be executed.
   * @param EntityInterface|NULL $target
   *   The optional target entity to check the operation against. If omitted
   *   the $entity parameter will be used as target.
   *
   * @return EntityInterface[]
   */
  private function scanEntity(EntityInterface $entity, $operation, EntityInterface $target = NULL) {
    $target = $target ?: $entity;
    $entities = [];
    foreach ($this->operationReferences($entity, $target, $operation) as $reference) {
      $entities[] = $reference;
    }
    return $entities;
  }

}