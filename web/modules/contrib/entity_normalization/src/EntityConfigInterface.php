<?php

namespace Drupal\entity_normalization;

/**
 * Provides an interface for the entity configuration.
 */
interface EntityConfigInterface {

  /**
   * Returns the format.
   *
   * @return string|null
   *   The format.
   */
  public function getFormat();

  /**
   * Returns the weight.
   *
   * @return int
   *   The weight.
   */
  public function getWeight();

  /**
   * The service normalizer names with optional format for this field.
   *
   * @return array
   *   A list of normalizer names.
   *   format -> normalize_service_name.
   */
  public function getNormalizers();

  /**
   * Gets a list of field definitions.
   *
   * @return \Drupal\entity_normalization\FieldConfigInterface[]
   *   List of field definitions.
   */
  public function getFields();

}
