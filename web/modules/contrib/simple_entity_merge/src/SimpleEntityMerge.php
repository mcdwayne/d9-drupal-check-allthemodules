<?php

namespace Drupal\simple_entity_merge;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Core\Entity\ContentEntityType;

/**
 * Class SimpleEntityMerge.
 */
class SimpleEntityMerge {

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
   * Constructs a new SimpleEntityMerge object.
   */
  public function __construct(EntityFieldManagerInterface $entity_field_manager, EntityTypeManagerInterface $entity_type_manager) {
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Merge references.
   *
   * Replace all references to the first entity id with references to the second
   * entity id.
   *
   * @param string $entity_type_id
   *   Entity type.
   * @param int $entity_source_id
   *   Source id.
   * @param int $entity_destination_id
   *   Destination id.
   */
  public function mergeReferences(string $entity_type_id, int $entity_source_id, int $entity_destination_id) {
    if (empty($entity_type_id) || empty($entity_source_id) || is_null($entity_destination_id)) {
      return FALSE;
    }

    $entity = entity_load($entity_type_id, $entity_source_id);
    if (!$entity instanceof EntityInterface) {
      return FALSE;
    }

    $fields = FieldStorageConfig::loadMultiple();
    foreach ($fields as $field_name => $field) {
      // The field is entity reference and the target is of the same type as
      // the entity we want to merge.
      if ($field->getType() == 'entity_reference' && $field->getSetting('target_type') == $entity->getEntityTypeId()) {
        $query = \Drupal::entityQuery($field->getTargetEntityTypeId());
        $query->condition($field->getName(), $entity->id());
        $ids = $query->execute();
        $entities = $this->entityTypeManager->getStorage($field->getTargetEntityTypeId())->loadMultiple($ids);
        foreach ($entities as $referencing_entity) {
          // Change the reference.
          foreach ($referencing_entity->{$field->getName()} as $delta => $reference) {
            if ($reference->target_id == $entity->id()) {
              $reference->target_id = $entity_destination_id;
            }
          }
          $referencing_entity->save();
        }
      }
    }

    // For base fields we do much the same thing, looking, for instance, for
    // nodes owned by a user we are merging.
    $all_entity_types = $this->entityTypeManager->getDefinitions();
    foreach ($all_entity_types as $key => $type) {
      // Config entities have no base fields.
      if ($type instanceof ContentEntityType) {
        $revision_keys = !empty($type->getRevisionMetadataKeys()) ? array_values($type->getRevisionMetadataKeys()) : [];
        $defs = $this->entityFieldManager->getBaseFieldDefinitions($key);
        foreach ($defs as $field_name => $base_field_definition) {
          if ($base_field_definition->getType() === 'entity_reference' && $base_field_definition->getSetting('target_type') == $entity->getEntityTypeId() && !in_array($field_name, $revision_keys)) {
            $bquery = \Drupal::entityQuery($base_field_definition->getTargetEntityTypeId());
            $bquery->condition($base_field_definition->getName(), $entity->id());
            $bids = $bquery->execute();
            $bentities = $this->entityTypeManager->getStorage($base_field_definition->getTargetEntityTypeId())->loadMultiple($bids);
            foreach ($bentities as $referencing_entity) {
              // Change the reference.
              foreach ($referencing_entity->{$base_field_definition->getName()} as $delta => $reference) {
                if ($reference->target_id == $entity->id()) {
                  $reference->target_id = $entity_destination_id;
                }
              }
              $referencing_entity->save();
            }
          }
        }
      }
    }
    return TRUE;
  }

}
