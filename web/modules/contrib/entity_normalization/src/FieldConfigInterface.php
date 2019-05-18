<?php

namespace Drupal\entity_normalization;

/**
 * Provides an interface for the field configuration.
 */
interface FieldConfigInterface {

  /**
   * Gets the configuration ID which represents a field name.
   *
   * @return string
   *   Configuration ID.
   */
  public function getId();

  /**
   * Gets the name used in the output for the field.
   *
   * @return string
   *   The name.
   */
  public function getName();

  /**
   * Is the field required?
   *
   * @return bool
   *   Indication if the field is required.
   */
  public function isRequired();

  /**
   * Gets the type of the field.
   *
   * @return string|null
   *   The type.
   */
  public function getType();

  /**
   * Gets the group where the field will be put in.
   *
   * @return string|null
   *   The group where the field will be put in or NULL when no group is set.
   */
  public function getGroup();

  /**
   * The service name of the normalizer for this field.
   *
   * @return string|null
   *   The service name of the normalizer
   *   or null when it should use default normalization.
   */
  public function getNormalizerName();

}
