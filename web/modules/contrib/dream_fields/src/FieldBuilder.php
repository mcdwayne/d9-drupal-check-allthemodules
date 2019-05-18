<?php

namespace Drupal\dream_fields;

/**
 * A builder object to collect field creation information.
 */
class FieldBuilder implements FieldBuilderInterface {

  /**
   * @var string
   */
  protected $label;

  /**
   * @var string
   */
  protected $bundle;

  /**
   * @var string
   */
  protected $entityTypeId;

  /**
   * @var bool
   */
  protected $required;

  /**
   * @var string
   */
  protected $fieldType;

  /**
   * @var array
   */
  protected $fieldStorageSettings;

  /**
   * @var array
   */
  protected $fieldSettings;

  /**
   * @var int
   */
  protected $fieldCardinality = 1;

  /**
   * @var string
   */
  protected $widgetId;

  /**
   * @var array
   */
  protected $widgetSettings;

  /**
   * @var string
   */
  protected $formatterId;

  /**
   * @var array
   */
  protected $formatterSettings;

  /**
   * @var string
   */
  protected $labelDisplay;

  /**
   * {@inheritdoc}
   */
  public function setLabel($label) {
    $this->label = $label;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setBundle($bundle) {
    $this->bundle = $bundle;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setEntityTypeId($entity_type) {
    $this->entityTypeId = $entity_type;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setRequired($required) {
    $this->required = $required;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setField($field_type, $storage_settings = [], $settings = []) {
    $this->fieldType = $field_type;
    $this->fieldStorageSettings = $storage_settings;
    $this->fieldSettings = $settings;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setWidget($widget, $settings = []) {
    $this->widgetId = $widget;
    $this->widgetSettings = $settings;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setDisplay($formatter, $settings = [], $label = 'inline') {
    $this->formatterId = $formatter;
    $this->formatterSettings = $settings;
    $this->labelDisplay = $label;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setCardinality($cardinality) {
    $this->fieldCardinality = $cardinality;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldType() {
    return $this->fieldType;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldStorageSettings() {
    return $this->fieldStorageSettings;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldSettings() {
    return $this->fieldSettings;
  }

  /**
   * {@inheritdoc}
   */
  public function getCardinality() {
    return $this->fieldCardinality;
  }

  /**
   * {@inheritdoc}
   */
  public function getWidget() {
    return $this->widgetId;
  }

  /**
   * {@inheritdoc}
   */
  public function getWidgetSettings() {
    return $this->widgetSettings;
  }

  /**
   * {@inheritdoc}
   */
  public function getDisplayFormatter() {
    return $this->formatterId;
  }

  /**
   * {@inheritdoc}
   */
  public function getDisplaySettings() {
    return $this->formatterSettings;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->label;
  }

  /**
   * {@inheritdoc}
   */
  public function getBundle() {
    return $this->bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityType() {
    return $this->entityTypeId;
  }

  /**
   * {@inheritdoc}
   */
  public function getRequired() {
    return $this->required;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabelDisplay() {
    return $this->labelDisplay;
  }

}
