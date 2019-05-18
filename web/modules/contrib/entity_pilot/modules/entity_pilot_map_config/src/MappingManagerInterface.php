<?php

namespace Drupal\entity_pilot_map_config;

use Drupal\entity_pilot\Data\FlightManifestInterface;

/**
 * Defines an interface for mapping manager.
 */
interface MappingManagerInterface {

  /**
   * Loads matching field and bundle mappings for a given difference.
   *
   * @param \Drupal\entity_pilot_map_config\ConfigurationDifferenceInterface $configuration_difference
   *   Configuration difference to load results for.
   *
   * @return \Drupal\entity_pilot_map_config\MatchingMappingsResult
   *   Matching results.
   */
  public function loadForConfigurationDifference(ConfigurationDifferenceInterface $configuration_difference);

  /**
   * Creates a bundle mapping from a given difference.
   *
   * @param \Drupal\entity_pilot_map_config\ConfigurationDifferenceInterface $configuration_difference
   *   Difference to create for.
   * @param \Drupal\entity_pilot\Data\FlightManifestInterface $flight_manifest
   *   Flight manifest.
   *
   * @return \Drupal\entity_pilot_map_config\BundleMappingInterface
   *   New bundle mapping.
   */
  public function createBundleMappingFromConfigurationDifference(ConfigurationDifferenceInterface $configuration_difference, FlightManifestInterface $flight_manifest);

  /**
   * Creates a field mapping from a given difference.
   *
   * @param \Drupal\entity_pilot_map_config\ConfigurationDifferenceInterface $configuration_difference
   *   Difference to create for.
   * @param \Drupal\entity_pilot\Data\FlightManifestInterface $flight_manifest
   *   Flight manifest.
   *
   * @return \Drupal\entity_pilot_map_config\FieldMappingInterface
   *   New field mapping.
   */
  public function createFieldMappingFromConfigurationDifference(ConfigurationDifferenceInterface $configuration_difference, FlightManifestInterface $flight_manifest);

}
