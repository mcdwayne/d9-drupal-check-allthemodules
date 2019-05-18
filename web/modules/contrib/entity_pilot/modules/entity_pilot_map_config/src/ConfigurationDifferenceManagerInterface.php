<?php

namespace Drupal\entity_pilot_map_config;

use Drupal\entity_pilot\Data\FlightManifestInterface;

/**
 * Defines an interface for configuration difference manager.
 */
interface ConfigurationDifferenceManagerInterface {

  /**
   * Computes the configuration difference between the flight and the site.
   *
   * Given the incoming flight, compares the fields, bundles and entity-types to
   * determine if any are absent on the destination site.
   *
   * @param \Drupal\entity_pilot\Data\FlightManifestInterface $flight
   *   Flight to compare to the site configuration.
   *
   * @return ConfigurationDifferenceInterface
   *   Configuration difference between the flight and the site.
   */
  public function computeDifference(FlightManifestInterface $flight);

}
