<?php

namespace Drupal\entity_pilot_map_config;

/**
 * Defines a value object for holding configuration differences.
 */
interface ConfigurationDifferenceInterface {

  /**
   * Gets nested array of missing bundles keyed by entity-type ID.
   *
   * @return array[]
   *   Nested array of missing bundles keyed by entity-type ID.
   */
  public function getMissingBundles();

  /**
   * Gets array of missing entity-type IDs.
   *
   * @return string[]
   *   Missing entity type IDs.
   */
  public function getMissingEntityTypes();

  /**
   * Gets array of missing field types keyed by field name and entity type ID.
   *
   * @return array[]
   *   Missing field-types keyed by entity type ID and field name.
   */
  public function getMissingFields();

  /**
   * Checks if there are missing fields.
   *
   * @return bool
   *   TRUE if there are missing fields.
   */
  public function hasMissingFields();

  /**
   * Checks if there are missing entity types.
   *
   * @return bool
   *   TRUE if there are missing entity types.
   */
  public function hasMissingEntityTypes();

  /**
   * Checks if there are missing bundles.
   *
   * @return bool
   *   TRUE if there are missing bundles.
   */
  public function hasMissingBundles();

  /**
   * Checks if a configuration difference exists.
   *
   * @return bool
   *   TRUE if there is a configuration difference that requires a mapping.
   */
  public function requiresMapping();

}
