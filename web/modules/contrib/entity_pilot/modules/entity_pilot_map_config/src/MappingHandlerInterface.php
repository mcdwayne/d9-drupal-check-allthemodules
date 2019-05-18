<?php

namespace Drupal\entity_pilot_map_config;

/**
 * Defines an interface for the mapping handler service.
 */
interface MappingHandlerInterface {

  /**
   * Applies a mapping pair to the given passengers.
   *
   * @param array $passengers
   *   Array of normalized passengers (hal+json).
   * @param \Drupal\entity_pilot_map_config\FieldMappingInterface $field_mapping
   *   Field mapping.
   * @param \Drupal\entity_pilot_map_config\BundleMappingInterface $bundle_mapping
   *   Bundle mapping.
   *
   * @return array
   *   Passengers with the mapping applied.
   */
  public function applyMappingPair(array $passengers, FieldMappingInterface $field_mapping, BundleMappingInterface $bundle_mapping);

}
