<?php

namespace Drupal\entity_reference_integrity;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Create a map of reference fields.
 */
class DependencyFieldMapGenerator implements DependencyFieldMapGeneratorInterface {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;


  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * The field type plugin ID.
   *
   * @var string
   */
  protected $fieldType;

  /**
   * The settings key for the target entity type ID.
   *
   * @var string
   */
  protected $targetEntitySettingsKey;

  /**
   * Construct a ReferenceFieldMapGenerator.
   */
  public function __construct(EntityFieldManagerInterface $entityFieldManager, EntityTypeManagerInterface $entityTypeManager, $fieldType, $targetEntitySettingsKey) {
    $this->entityFieldManager = $entityFieldManager;
    $this->entityTypeManager = $entityTypeManager;
    $this->fieldType = $fieldType;
    $this->targetEntitySettingsKey = $targetEntitySettingsKey;
  }

  /**
   * {@inheritdoc}
   */
  public function getReferentialFieldMap() {
    $referential_field_map = [];

    foreach ($this->entityFieldManager->getFieldMapByFieldType($this->fieldType) as $source_entity_type_id => $fields) {

      $source_entity_storage_definitions = $this->entityFieldManager->getFieldStorageDefinitions($source_entity_type_id);
      $source_entity_type = $this->entityTypeManager->getDefinition($source_entity_type_id);

      foreach ($fields as $field_name => $field_data) {
        $field_storage_definition = !empty($source_entity_storage_definitions[$field_name]) ? $source_entity_storage_definitions[$field_name] : FALSE;
        // Some fields like computed fields do not show up in the return value
        // of getFieldStorageDefinitions.
        if (!$field_storage_definition) {
          continue;
        }
        // Fields with custom storage like term parents can't be used with
        // entity query.
        if ($field_storage_definition->hasCustomStorage()) {
          continue;
        }

        // Don't query against revision metadata fields.
        if (in_array($field_name, $source_entity_type->getRevisionMetadataKeys())) {
          continue;
        }

        $target_entity_type_id = $source_entity_storage_definitions[$field_name]->getSetting($this->targetEntitySettingsKey);
        $referential_field_map[$target_entity_type_id][$source_entity_type_id][] = $field_name;
      }
    }
    return $referential_field_map;
  }

  /**
   * {@inheritdoc}
   */
  public function getReferencingFields($entity_type_id) {
    $map = $this->getReferentialFieldMap();
    return isset($map[$entity_type_id]) ? $map[$entity_type_id] : [];
  }

}
