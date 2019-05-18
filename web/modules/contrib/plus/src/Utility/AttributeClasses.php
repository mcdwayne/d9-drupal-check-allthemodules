<?php

namespace Drupal\plus\Utility;

use Drupal\Component\Utility\Html;

/**
 * A class that defines a class based Attribute.
 */
class AttributeClasses extends AttributeArray {

  /**
   * {@inheritdoc}
   */
  public function __construct(array &$value = []) {
    parent::__construct('class', $value);
  }

  /**
   * {@inheritdoc}
   */
  protected function sanitize(...$values) {
    return parent::sanitize($values)
      // Split classes added as a string using a space separator.
      ->map(function ($value) {
        return explode(' ', $value);
      })

      // Flatten again since it was just split into arrays.
      ->flatten()

      // Clean each class to ensure it's a valid class identifier.
      ->map(function ($string) {
        return Html::cleanCssIdentifier($string);
      });
  }

  /**
   * Replaces a class in the attributes array.
   *
   * @param string $oldClassName
   *   The old class to remove.
   * @param string $newClassName
   *   The new class. It will not be added if the $old class does not exist.
   * @param bool $onlyIfExists
   *   (optional) Flag indicating whether to add $newClassName only if
   *   $oldClassName exists, defaults to TRUE.
   *
   * @return static
   *
   * @see \Drupal\plus\Utility\Attributes::getClasses()
   */
  public function replaceClass($oldClassName, $newClassName, $onlyIfExists = TRUE) {
    $classes = &$this->value();
    $key = array_search($oldClassName, $classes);
    if (!$onlyIfExists || $key !== FALSE) {
      $classes = array_slice($classes, $key, 1);
      $classes[] = $newClassName;
      $this->replace($classes);
    }
    return $this;
  }

}
