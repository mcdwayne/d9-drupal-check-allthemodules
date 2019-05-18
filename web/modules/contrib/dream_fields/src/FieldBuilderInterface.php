<?php

namespace Drupal\dream_fields;

/**
 * An interface to assist with field creation.
 */
interface FieldBuilderInterface {

  /**
   * Set the label which will be used during field creation.
   *
   * @param string $label
   *   The label.
   *
   * @return static
   */
  public function setLabel($label);

  /**
   * Set the bundle which will be used during field creation.
   *
   * @param string $bundle
   *   The the bundle to use during field creation.
   *
   * @return static
   */
  public function setBundle($bundle);

  /**
   * Set the entity which will be used during field creation.
   *
   * @param string $entity_type
   *   The entity type ID to use during field creation.
   *
   * @return static
   */
  public function setEntityTypeId($entity_type);

  /**
   * If field should be required or not.
   *
   * @param boolean $required
   *   If fields should be required or not.
   *
   * @return static
   */
  public function setRequired($required);

  /**
   * Set the field.
   *
   * @param string $field_type
   *   The type of field to create.
   * @param array $storage_settings
   *   The storage settings.
   * @param array $settings
   *   The instance settings.
   *
   * @return static
   */
  public function setField($field_type, $storage_settings = [], $settings = []);

  /**
   * Set the cardinality.
   *
   * @param int $cardinality
   *   The field cardinality.
   *
   * @return static
   */
  public function setCardinality($cardinality);

  /**
   * Set a widget.
   *
   * @param string $widget
   *   The widget to set.
   * @param array $settings
   *   The widget settings.
   *
   * @return static
   */
  public function setWidget($widget, $settings = []);

  /**
   * Set the display formatter settings.
   *
   * @param string $formatter
   *   The field formatter ID.
   * @param array $settings
   *   What settings should be set for display.
   * @param string $label
   *   The label option.
   *
   * @return static
   */
  public function setDisplay($formatter, $settings = [], $label = 'inline');

  /**
   * Get the field type.
   *
   * @return string
   *   The field type.
   */
  public function getFieldType();

  /**
   * Get the field storage settings.
   *
   * @return string
   *   The field storage settings.
   */
  public function getFieldStorageSettings();

  /**
   * Get the field settings.
   *
   * @return string
   *   The field settings.
   */
  public function getFieldSettings();

  /**
   * Get the cardinality.
   *
   * @return string
   *   The cardinality.
   */
  public function getCardinality();

  /**
   * Get the widget ID.
   *
   * @return string
   *   The widget ID.
   */
  public function getWidget();

  /**
   * Get the widget settings.
   *
   * @return string
   *   The widget settings.
   */
  public function getWidgetSettings();

  /**
   * Get the display formatter ID.
   *
   * @return string
   *   The display formatter ID.
   */
  public function getDisplayFormatter();

  /**
   * Get the
   *
   * @return string
   *   The
   */
  public function getDisplaySettings();

  /**
   * Get the display settings.
   *
   * @return string
   *   The display settings.
   */
  public function getLabel();

  /**
   * Get the bundle.
   *
   * @return string
   *   The bundle.
   */
  public function getBundle();

  /**
   * Get the entity.
   *
   * @return string
   *   The entity.
   */
  public function getEntityType();

  /**
   * Get the required state.
   *
   * @return string
   *   The required state.
   */
  public function getRequired();

  /**
   * Get the label display.
   *
   * @return string
   *   The label display.
   */
  public function getLabelDisplay();

}
