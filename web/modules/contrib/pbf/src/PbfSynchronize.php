<?php

namespace Drupal\pbf;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\field\FieldConfigInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Class PbfSynchronize.
 *
 * @package Drupal\pbf
 */
class PbfSynchronize implements PbfSynchronizeInterface {

  /**
   * Drupal\Core\Entity\EntityFieldManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * PbfSynchronize constructor.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   */
  public function __construct(EntityFieldManagerInterface $entity_field_manager, EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory) {
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * Synchronize a Pbf field form entity.
   *
   * @param string $op
   *   The operation to run.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to synchronize.
   */
  public function synchronize($op, EntityInterface $entity) {
    $fields = $this->findPbfFieldsSynchronized($entity);

    if (empty($fields)) {
      return;
    }
    /** @var \Drupal\field\FieldConfigInterface $fieldConfig */
    foreach ($fields as $field_name => $fieldConfig) {
      if ($target_field_id = $this->isSynchronizedWith($fieldConfig)) {
        $this->synchronizeTarget($op, $target_field_id, $fieldConfig, $entity);
      }
      elseif ($source_field_id = $this->isSynchronizedBy($fieldConfig)) {
        $this->synchronizeTarget($op, $source_field_id, $fieldConfig, $entity);
      }
      else {
        return;
      }
    }

  }

  /**
   * Check if a field has an unlimited cardinality.
   *
   * @param \Drupal\field\FieldConfigInterface $fieldConfig
   *   The field instance to verify.
   *
   * @return bool
   *   Return TRUE if cardinality is unlimited.
   */
  protected function hasUnlimitedCardinality(FieldConfigInterface $fieldConfig) {
    return $fieldConfig->getFieldStorageDefinition()->getCardinality() === FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED;
  }

  /**
   * Find pbf fields synchronized attached on an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity on which search Pbf fields.
   *
   * @return array $pbf_fields
   *   An array of Pbf fields found.
   */
  protected function findPbfFieldsSynchronized(EntityInterface $entity) {
    $pbf_fields = [];
    // Check if a Pbf field is attached to the entity.
    /* @var  \Drupal\Core\Entity\FieldableEntityInterface $entity */
    $fields = $entity->getFieldDefinitions();
    /** @var \Drupal\Core\Field\FieldConfigInterface $field */
    foreach ($fields as $field_name => $field) {
      if ($field instanceof FieldConfigInterface && $field->getType() == 'pbf' && $this->hasUnlimitedCardinality($field)) {
        if ($this->isSynchronizedWith($field) || $this->isSynchronizedBy($field)) {
          $pbf_fields[$field_name] = $field;
        }
      }
    }
    return $pbf_fields;
  }

  /**
   * Check if a Pbf field synchronize other field.
   *
   * @param \Drupal\field\FieldConfigInterface $fieldConfig
   *   The field config entity.
   *
   * @return mixed string|bool
   *   The field id to synchronize or FALSE.
   */
  protected function isSynchronizedWith(FieldConfigInterface $fieldConfig) {
    return $fieldConfig->getSetting('synchronized_with') ? $fieldConfig->getSetting('synchronized_with') : FALSE;
  }

  /**
   * Check if a Pbf field is synchronized by other field and can synchronize it.
   *
   * @param \Drupal\field\FieldConfigInterface $fieldConfig
   *   The field config entity.
   *
   * @return mixed string|bool
   *   The field id to synchronize or FALSE.
   */
  protected function isSynchronizedBy(FieldConfigInterface $fieldConfig) {
    return ($fieldConfig->getSetting('synchronized_by') && $fieldConfig->getSetting('synchronized_from_target')) ? $fieldConfig->getSetting('synchronized_by') : FALSE;
  }

  /**
   * Gets all the referenced entity IDs from a specific field on an entity.
   *
   * @param EntityInterface $entity
   *   The entity to scan for references.
   * @param FieldConfigInterface $field
   *   Field config definition.
   *
   * @return array
   *   Array of unique ids, empty if there are no references or the field
   *   does not exist on $entity.
   */
  private function getReferenceIds(EntityInterface $entity, FieldConfigInterface $field) {
    $ids = array();
    $field_name = $field->getName();
    /** @var \Drupal\Core\Entity\FieldableEntityInterface $entity */
    if ($entity->hasField($field_name)) {
      foreach ($entity->get($field_name)->getValue() as $delta => $reference) {
        $ids[$delta] = $reference['target_id'];
      }
    }
    return array_unique(array_filter($ids));
  }

  /**
   * Launch the synchronization belong the operation done on the entity.
   *
   * @param string $op
   *   The operation to do.
   * @param string $field_id
   *   The field id of the Pbf field on target entity.
   * @param \Drupal\field\FieldConfigInterface $field
   *   The Pbf field synchroniezd.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity on which the operation is performed.
   */
  protected function synchronizeTarget($op, $field_id, FieldConfigInterface $field, EntityInterface $entity) {
    $target_entity_type_id = $field->getSetting('target_type');

    /** @var \Drupal\field\FieldConfigInterface $target_field */
    $target_field = $this->entityTypeManager->getStorage('field_config')->load($field_id);

    // We check yet if both fields has an unlimited cardinality. In case where
    // the field's cardinality has been changed after been configured to be
    // synchronized.
    if (!$this->hasUnlimitedCardinality($field) || !$this->hasUnlimitedCardinality($target_field)) {
      return;
    }

    switch ($op) {
      case 'insert':
        $this->insert($target_entity_type_id, $target_field, $entity, $field);
        break;

      case 'delete':
        $this->delete($target_entity_type_id, $target_field, $entity, $field);
        break;

      case 'update':
        $this->update($target_entity_type_id, $target_field, $entity, $field);
        break;

      default:
        break;
    }
  }

  /**
   * Update entities ref. by a Pbf field synchronized when entity is updated.
   *
   * @param string $target_entity_type_id
   *   The entity type id of targeted entity.
   * @param \Drupal\field\FieldConfigInterface $target_field
   *   The Pbf field synchronized by on the referenced entity.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity updated.
   * @param \Drupal\field\FieldConfigInterface $field
   *   The Pbf field synchronized with on the entity.
   */
  protected function update($target_entity_type_id, FieldConfigInterface $target_field, EntityInterface $entity, FieldConfigInterface $field) {
    $entity_original = isset($entity->original) ? $entity->original : $entity;
    $target_field_name = $target_field->getName();
    $target_ids = $this->getReferenceIds($entity, $field);
    // Param added not used. We synchronize always the referenced entities.
    $added = array_diff($this->getReferenceIds($entity, $field), $this->getReferenceIds($entity_original, $field));
    // We synchronize all targeted entities.
    if ($target_ids) {
      $target_entities = $this->entityTypeManager->getStorage($target_entity_type_id)->loadMultiple($target_ids);
      /** @var \Drupal\Core\Entity\FieldableEntityInterface $target_entity */
      foreach ($target_entities as $target_entity) {
        $ids_referenced_from_target = $this->getReferenceIds($target_entity, $target_field);
        if (!in_array($entity->id(), $ids_referenced_from_target)) {
          if ($target_entity->hasField($target_field_name)) {
            $target_entity->{$target_field_name}->appendItem($entity->id());
            $target_entity->save();
          }
        }
      }
    }

    // If some entities referenced are removed, we removed the
    // reference to the entity from these entities.
    $deleted = array_diff($this->getReferenceIds($entity_original, $field), $this->getReferenceIds($entity, $field));
    if ($deleted) {
      $target_entities = $this->entityTypeManager->getStorage($target_entity_type_id)->loadMultiple($deleted);
      foreach ($target_entities as $target_entity) {
        $ids_referenced_from_target = $this->getReferenceIds($target_entity, $target_field);
        foreach ($ids_referenced_from_target as $delta => $reference) {
          if ($reference == $entity->id()) {
            if ($target_entity->hasField($target_field_name)) {
              $target_entity->get($target_field_name)->removeItem($delta);
              $target_entity->save();
            }
          }
        }
      }
    }

  }

  /**
   * Update entities ref. by a Pbf field synchronized when entity is created.
   *
   * @param string $target_entity_type_id
   *   The entity type id of targeted entity.
   * @param \Drupal\field\FieldConfigInterface $target_field
   *   The Pbf field synchronized by on the referenced entity.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity updated.
   * @param \Drupal\field\FieldConfigInterface $field
   *   The Pbf field synchronized with on the entity.
   */
  protected function insert($target_entity_type_id, FieldConfigInterface $target_field, EntityInterface $entity, FieldConfigInterface $field) {
    $target_field_name = $target_field->getName();
    $target_ids = $this->getReferenceIds($entity, $field);
    if ($target_ids) {
      $target_entities = $this->entityTypeManager->getStorage($target_entity_type_id)->loadMultiple($target_ids);
      /** @var \Drupal\Core\Entity\FieldableEntityInterface $target_entity */
      foreach ($target_entities as $target_entity) {
        $ids_referenced_from_target = $this->getReferenceIds($target_entity, $target_field);
        if (!in_array($entity->id(), $ids_referenced_from_target)) {
          if ($target_entity->hasField($target_field_name)) {
            $target_entity->{$target_field_name}->appendItem($entity->id());
            $target_entity->save();
          }
        }
      }
    }
  }

  /**
   * Update entities ref. by a Pbf field synchronized when entity is deleted.
   *
   * @param string $target_entity_type_id
   *   The entity type id of targeted entity.
   * @param \Drupal\field\FieldConfigInterface $target_field
   *   The Pbf field synchronized by on the referenced entity.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity updated.
   * @param \Drupal\field\FieldConfigInterface $field
   *   The Pbf field synchronized with on the entity.
   */
  protected function delete($target_entity_type_id, FieldConfigInterface $target_field, EntityInterface $entity, FieldConfigInterface $field) {
    $target_field_name = $target_field->getName();
    $target_ids = $this->getReferenceIds($entity, $field);
    if ($target_ids) {
      $target_entities = $this->entityTypeManager->getStorage($target_entity_type_id)->loadMultiple($target_ids);
      foreach ($target_entities as $target_entity) {
        $ids_referenced_from_target = $this->getReferenceIds($target_entity, $target_field);
        foreach ($ids_referenced_from_target as $delta => $reference) {
          if ($reference == $entity->id()) {
            if ($target_entity->hasField($target_field_name)) {
              $target_entity->get($target_field_name)->removeItem($delta);
              $target_entity->save();
            }
          }
        }
      }
    }
  }

}
