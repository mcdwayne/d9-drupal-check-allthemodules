<?php

namespace Drupal\entity_normalization;

/**
 * Provides an implementation for the field configuration plugin.
 */
class FieldConfig implements FieldConfigInterface {

  /**
   * The configuration ID which represents a field name.
   *
   * @var string
   */
  protected $id;

  /**
   * The field definition.
   *
   * @var array
   */
  protected $definition;

  /**
   * Constructor.
   *
   * @param string $id
   *   The configuration ID which represents a field name.
   * @param array|null $definition
   *   The field definition, can be NULL for the default definition.
   */
  public function __construct($id, array $definition = NULL) {
    $this->id = $id;
    $this->definition = $definition !== NULL ? $definition : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return isset($this->definition['name']) ? $this->definition['name'] : $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function isRequired() {
    return isset($this->definition['required']) ? (bool) $this->definition['required'] : TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return isset($this->definition['type']) ? $this->definition['type'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroup() {
    return isset($this->definition['group']) ? $this->definition['group'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getNormalizerName() {
    return isset($this->definition['normalizer']) ? $this->definition['normalizer'] : NULL;
  }

}
