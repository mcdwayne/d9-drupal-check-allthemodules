<?php

namespace Drupal\entity_normalization;

use Drupal\Core\Plugin\PluginBase;

/**
 * Provides an implementation for the entity configuration plugin.
 */
class EntityConfig extends PluginBase implements EntityConfigInterface {

  /**
   * A list of field configurations.
   *
   * @var \Drupal\entity_normalization\FieldConfigInterface[]
   */
  protected $fields;

  /**
   * {@inheritdoc}
   */
  public function getFormat() {
    return $this->pluginDefinition['format'] ?: NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return (int) $this->pluginDefinition['weight'];
  }

  /**
   * {@inheritdoc}
   */
  public function getNormalizers() {
    return isset($this->pluginDefinition['normalizers']) ? $this->pluginDefinition['normalizers'] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getFields() {
    if (!isset($this->fields)) {
      $this->fields = [];
      if (isset($this->pluginDefinition['fields'])) {
        foreach ($this->pluginDefinition['fields'] as $fieldName => $fieldConfig) {
          $this->fields[$fieldName] = new FieldConfig($fieldName, $fieldConfig);
        }
      }
    }
    return $this->fields;
  }

}
