<?php

namespace Drupal\entity_pilot_map_config;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Field mapping entities.
 */
interface FieldMappingInterface extends ConfigEntityInterface {

  /**
   * Destination field name for ignoring fields.
   */
  const IGNORE_FIELD = '_null';

  /**
   * Adds a new field mapping.
   *
   * @param array $mapping
   *   Mapping with keys entity_type, source_field_name and
   *   destination_field_name.
   *
   * @return self
   *   Called instance.
   */
  public function addMapping(array $mapping);

  /**
   * Gets the current mappings.
   *
   * @return array
   *   Mappings.
   */
  public function getMappings();

  /**
   * Sets the field mappings.
   *
   * @param array $mappings
   *   Array of mappings, each containing keys entity_type, source_field_name
   *   and destination_field_name.
   *
   * @return self
   *   Called instance.
   */
  public function setMappings(array $mappings);

}
