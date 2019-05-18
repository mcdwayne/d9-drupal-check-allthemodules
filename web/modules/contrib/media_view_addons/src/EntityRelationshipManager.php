<?php

namespace Drupal\media_view_addons;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Database\Connection as DatabaseConnection;

/**
 * Manage relationships between entities.
 */
class EntityRelationshipManager implements EntityRelationshipManagerInterface {
  /**
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * EntityRelationshipManager constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   * @param \Drupal\Core\Database\Connection $database
   */
  public function __construct(
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
    EntityFieldManagerInterface $entity_field_manager,
    DatabaseConnection $database
  ) {
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->entityFieldManager = $entity_field_manager;
    $this->database = $database;
  }

  /**
   * Get fields that reference entities.
   *
   * @param array $entity_types
   * @return array
   */
  protected function entityReferenceFieldMap($entity_types = ['node', 'paragraph']) {
    static $entity_reference_map;
    if (is_array($entity_reference_map)) {
      return $entity_reference_map;
    }

    foreach ($entity_types as $entity_type_id) {
      $bundles = $this->entityTypeBundleInfo->getBundleInfo($entity_type_id);
      foreach ($bundles as $bundle_id => $bundle) {
        $field_definitions = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle_id);
        foreach ($field_definitions as $field_definition) {
          if ($target_type = $field_definition->getSetting('target_type')) {
            $entity_reference_map[$target_type][$entity_type_id][$field_definition->getName()] = $field_definition;
          }
        }
      }
    }

    return $entity_reference_map;
  }

  /**
   * Return fields that reference provided entity type.
   *
   * @param $entity_type_id
   * @return array
   */
  protected function entityReferenceFields($entity_type_id) {
    $map = $this->entityReferenceFieldMap();
    return $map[$entity_type_id] ?: [];
  }

  /**
   * @inheritdoc
   */
  public function topLevelNids($entity_type_id, $entity_id, $nesting_level = 0, $nesting_limit = 5) {
    // Prevent infinite loop.
    if ($nesting_level >= $nesting_limit) {
      return [];
    }

    $nids = [];
    foreach ($this->entityReferenceFields($entity_type_id) as $parent_entity_type_id => $field_map) {
      $field_names = array_keys($field_map);
      foreach ($field_names as $field_name) {
        $query = $this->database->select($parent_entity_type_id . '__' . $field_name, 'enf')
          ->condition($field_name . '_target_id', $entity_id, '=')
          ->fields('enf', ['entity_id']);
        $result = $query->execute()->fetchAllAssoc('entity_id');
        foreach ($result as $row) {
          if ($parent_entity_type_id == 'node') {
            $nids[] = intval($row->entity_id);
          }
          else {
            $nids = array_merge($nids, $this->topLevelNids($parent_entity_type_id, $row->entity_id, $nesting_level++));
          }
        }
      }
    }
    return $nids;
  }
}
