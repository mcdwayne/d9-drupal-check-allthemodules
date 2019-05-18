<?php

namespace Drupal\entity_pilot_map_config;

/**
 * Defines a value object for holding an array of field and bundle mappings.
 */
class MatchingMappingsResult {

  /**
   * Field mappings.
   *
   * @var \Drupal\entity_pilot_map_config\FieldMappingInterface[]
   */
  protected $fieldMappings;

  /**
   * Bundle mappings.
   *
   * @var \Drupal\entity_pilot_map_config\BundleMappingInterface[]
   */
  protected $bundleMappings;

  /**
   * Constructs a new MatchingMappingsResult object.
   *
   * @param \Drupal\entity_pilot_map_config\BundleMappingInterface[] $bundle_mappings
   *   Bundle mappings.
   * @param \Drupal\entity_pilot_map_config\FieldMappingInterface[] $field_mappings
   *   Field mappings.
   */
  public function __construct(array $bundle_mappings, array $field_mappings) {
    $this->bundleMappings = $bundle_mappings;
    $this->fieldMappings = $field_mappings;
  }

  /**
   * Gets bundle mappings for the result..
   *
   * @return BundleMappingInterface[]
   *   Bundle mappings for the result.
   */
  public function getBundleMappings() {
    return $this->bundleMappings;
  }

  /**
   * Gets field mappings for the result.
   *
   * @return FieldMappingInterface[]
   *   Field mappings for the result.
   */
  public function getFieldMappings() {
    return $this->fieldMappings;
  }

}
