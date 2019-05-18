<?php

namespace Drupal\acquia_contenthub\Event;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\depcalc\DependencyStack;
use Symfony\Component\EventDispatcher\Event;

/**
 * Unserializes ContentEntity fields syndicated from CDF.
 *
 * The CDF sends entity data via the "data" attribute. This data is base64
 * encoded by default, and by the time it reaches this event, it is a single
 * piece of field level data including the data necessary to identify and
 * unserialize the value back into native Drupal field data.
 */
class UnserializeCdfEntityFieldEvent extends Event {

  /**
   * The entity type.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityType;

  /**
   * The entity bundle.
   *
   * @var string
   */
  protected $bundle;

  /**
   * The field name.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * The field array.
   *
   * @var array
   */
  protected $field;

  /**
   * The field metadata.
   *
   * @var array
   */
  protected $metadata;

  /**
   * The dependency stack.
   *
   * @var \Drupal\depcalc\DependencyStack
   */
  protected $stack;

  /**
   * The value array.
   *
   * @var array
   */
  protected $value = [];

  /**
   * UnserializeCdfEntityFieldEvent constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type id.
   * @param string $bundle
   *   The entity bundle.
   * @param string $field_name
   *   The field name.
   * @param array $field
   *   The field array.
   * @param array $metadata
   *   The metadata array.
   * @param \Drupal\depcalc\DependencyStack $stack
   *   The dependency stack.
   */
  public function __construct(EntityTypeInterface $entity_type, $bundle, $field_name, array $field, array $metadata, DependencyStack $stack) {
    $this->entityType = $entity_type;
    $this->bundle = $bundle;
    $this->fieldName = $field_name;
    $this->field = $field;
    $this->metadata = $metadata;
    $this->stack = $stack;
  }

  /**
   * Get the entity type for this field.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface
   *   The entity type for this field.
   */
  public function getEntityType() {
    return $this->entityType;
  }

  /**
   * Gets the entity bundle for this field.
   *
   * @return string
   */
  public function getBundle() {
    return $this->bundle;
  }

  /**
   * Get the field name to field values to.
   *
   * @return string
   *   The field name.
   */
  public function getFieldName() {
    return $this->fieldName;
  }

  /**
   * Change field name in the very narrow case where one field affects another.
   *
   * @param string $field_name
   *   The field name.
   */
  public function setFieldName($field_name) {
    $this->fieldName = $field_name;
  }

  /**
   * The CDF field values.
   *
   * @return array
   *   The array of value.
   */
  public function getField() {
    return $this->field;
  }

  /**
   * The field type.
   *
   * @return array
   *   The array of value.
   */
  public function getFieldMetadata() {
    return $this->metadata;
  }

  /**
   * Set the value use to instantiate an entity's field values.
   *
   * @param array $value
   *   The array of value.
   */
  public function setValue(array $value) {
    $this->value = $value;
  }

  /**
   * Get the value intended to be used to instantiate a field's values.
   *
   * @return array
   *   The array of value.
   */
  public function getValue() {
    return $this->value;
  }

  /**
   * Get the dependency stack.
   *
   * @return \Drupal\depcalc\DependencyStack
   *   The dependency stack.
   */
  public function getStack() {
    return $this->stack;
  }

}
