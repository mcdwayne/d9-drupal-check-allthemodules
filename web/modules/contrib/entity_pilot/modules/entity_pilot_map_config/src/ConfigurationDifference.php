<?php

namespace Drupal\entity_pilot_map_config;

/**
 * Defines a value object for holding configuration differences.
 */
class ConfigurationDifference implements ConfigurationDifferenceInterface {

  /**
   * Array of missing field types keyed by entity-type ID and name.
   *
   * @var array[]
   */
  protected $missingFields = [];

  /**
   * Array of missing bundles keyed by entity-type ID.
   *
   * @var array[]
   */
  protected $missingBundles = [];

  /**
   * Array of missing entity-type IDs.
   *
   * @var string[]
   */
  protected $missingEntityTypes = [];

  /**
   * Constructs a new ConfigurationDifference object.
   *
   * @param array[] $missing_fields
   *   Missing fields keyed by entity-type ID and field name.
   * @param array[] $missing_bundles
   *   Missing bundles, keyed by entity-type ID.
   * @param string[] $missing_entity_types
   *   Missing entity type IDs.
   */
  public function __construct(array $missing_fields, array $missing_bundles = [], array $missing_entity_types = []) {
    $this->missingBundles = $missing_bundles;
    $this->missingEntityTypes = $missing_entity_types;
    $this->missingFields = $missing_fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getMissingBundles() {
    return $this->missingBundles;
  }

  /**
   * {@inheritdoc}
   */
  public function getMissingEntityTypes() {
    return $this->missingEntityTypes;
  }

  /**
   * {@inheritdoc}
   */
  public function getMissingFields() {
    return $this->missingFields;
  }

  /**
   * {@inheritdoc}
   */
  public function hasMissingFields() {
    return !empty($this->getMissingFields());
  }

  /**
   * {@inheritdoc}
   */
  public function hasMissingEntityTypes() {
    return !empty($this->getMissingEntityTypes());
  }

  /**
   * {@inheritdoc}
   */
  public function hasMissingBundles() {
    return !empty($this->getMissingBundles());
  }

  /**
   * {@inheritdoc}
   */
  public function requiresMapping() {
    return $this->hasMissingBundles() || $this->hasMissingEntityTypes() || $this->hasMissingFields();
  }

}
