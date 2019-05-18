<?php

namespace Drupal\entity_pilot_map_config;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Bundle mapping entities.
 */
interface BundleMappingInterface extends ConfigEntityInterface {

  /**
   * Ignore this bundle.
   */
  const IGNORE_BUNDLE = '_null';

  /**
   * Adds a new bundle mapping.
   *
   * @param array $mapping
   *   Mapping with keys entity_type, source_bundle_name and
   *   destination_bundle_name.
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
   * Sets the bundle mappings.
   *
   * @param array $mappings
   *   Array of mappings, each containing keys entity_type, source_bundle_name
   *   and destination_bundle_name.
   *
   * @return self
   *   Called instance.
   */
  public function setMappings(array $mappings);

}
