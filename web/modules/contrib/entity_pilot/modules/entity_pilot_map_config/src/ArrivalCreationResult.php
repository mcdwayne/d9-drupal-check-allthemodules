<?php

namespace Drupal\entity_pilot_map_config;

/**
 * Defines a value object for arrival creation decision support.
 */
class ArrivalCreationResult {

  /**
   * Field mapping to use.
   *
   * @var \Drupal\entity_pilot_map_config\FieldMappingInterface|null
   */
  protected $fieldMapping;

  /**
   * Bundle mapping to use.
   *
   * @var \Drupal\entity_pilot_map_config\BundleMappingInterface|null
   */
  protected $bundleMapping;

  /**
   * Array of routing destinations with keys route_name and route_parameters.
   *
   * @var array
   */
  protected $destinations = [];

  /**
   * Constructs a new ArrivalCreationResult object.
   *
   * @param \Drupal\entity_pilot_map_config\BundleMappingInterface $bundle_mapping
   *   Bundle mapping for the result.
   * @param \Drupal\entity_pilot_map_config\FieldMappingInterface $field_mapping
   *   Field mapping for the result.
   * @param array $destinations
   *   Routing destinations to handle configuration of new arrival in sequence.
   */
  public function __construct(BundleMappingInterface $bundle_mapping = NULL, FieldMappingInterface $field_mapping = NULL, array $destinations = []) {
    $this->bundleMapping = $bundle_mapping;
    $this->destinations = $destinations;
    $this->fieldMapping = $field_mapping;
  }

  /**
   * Gets bundle mapping to suit created arrival.
   *
   * @return BundleMappingInterface|null
   *   Resultant bundle mapping.
   */
  public function getBundleMapping() {
    return $this->bundleMapping;
  }

  /**
   * Gets redirect destinations for given result.
   *
   * @return array
   *   Value of destinations
   */
  public function getDestinations() {
    return $this->destinations;
  }

  /**
   * Gets field mapping to suit created arrival.
   *
   * @return FieldMappingInterface|null
   *   Resultant field mapping.
   */
  public function getFieldMapping() {
    return $this->fieldMapping;
  }

}
