<?php

namespace Drupal\responsive_class_field\Plugin\DataType;

use Drupal\Core\TypedData\TypedData;

/**
 * A computed property for generated CSS classes.
 *
 * @DataType(
 *   id = "responsive_class_classes",
 *   label = @Translation("CSS classes"),
 *   description = @Translation("Generated CSS classes of the responsive class field."),
 * )
 */
class ResponsiveClassClassesData extends TypedData {

  /**
   * Array of generated classes.
   *
   * @var array
   */
  protected $classes;

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    return isset($this->classes) ? $this->classes : [];
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($value, $notify = TRUE) {
    $this->classes = $value;

    // Notify the parent of any changes.
    if ($notify && isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getString() {
    return implode(' ', $this->getValue());
  }

}
