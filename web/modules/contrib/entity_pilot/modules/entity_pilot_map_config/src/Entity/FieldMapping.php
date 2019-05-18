<?php

namespace Drupal\entity_pilot_map_config\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\entity_pilot_map_config\FieldMappingInterface;

/**
 * Defines the Field mapping entity.
 *
 * @ConfigEntityType(
 *   id = "ep_field_mapping",
 *   label = @Translation("Field mapping"),
 *   handlers = {
 *     "list_builder" = "Drupal\entity_pilot_map_config\FieldMappingListBuilder",
 *     "form" = {
 *       "add" = "Drupal\entity_pilot_map_config\Form\FieldMappingForm",
 *       "edit" = "Drupal\entity_pilot_map_config\Form\FieldMappingForm",
 *       "delete" = "Drupal\entity_pilot_map_config\Form\FieldMappingDeleteForm"
 *     }
 *   },
 *   config_prefix = "ep_field_mapping",
 *   admin_permission = "administer entity_pilot field mappings",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/entity-pilot/field-mappings/{ep_field_mapping}",
 *     "edit-form" = "/admin/structure/entity-pilot/field-mappings/{ep_field_mapping}/edit",
 *     "delete-form" = "/admin/structure/entity-pilot/field-mappings/{ep_field_mapping}/delete",
 *     "collection" = "/admin/structure/entity-pilot/field-mappings"
 *   }
 * )
 */
class FieldMapping extends ConfigEntityBase implements FieldMappingInterface {

  /**
   * The Field mapping ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Field mapping label.
   *
   * @var string
   */
  protected $label;

  /**
   * The field mappings.
   *
   * @var array
   */
  protected $mappings = [];

  /**
   * {@inheritdoc}
   */
  public function addMapping(array $mapping) {
    $this->mappings[] = $mapping;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMappings() {
    return $this->mappings;
  }

  /**
   * {@inheritdoc}
   */
  public function setMappings(array $mappings) {
    $this->mappings = $mappings;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();
    $prefix = 'field.storage.';
    foreach ($this->mappings as $mapping) {
      if ($mapping['destination_field_name'] === self::IGNORE_FIELD) {
        continue;
      }
      $this->addDependency('config', $prefix . $mapping['entity_type'] . '.' . $mapping['destination_field_name']);
    }
    return $this;
  }

}
