<?php

namespace Drupal\responsive_class_field\Plugin\DataType;

use Drupal\Core\TypedData\TypedData;

/**
 * Provides a data type for responsive class values.
 *
 * @DataType(
 *   id = "responsive_class",
 *   label = @Translation("Responsive Class"),
 *   description = @Translation("A responsive class field."),
 * )
 */
class ResponsiveClassData extends TypedData {

  protected $value;

}
