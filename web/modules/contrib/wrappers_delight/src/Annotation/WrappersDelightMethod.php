<?php

namespace Drupal\wrappers_delight\Annotation;

/**
 * Defines a Wrappers Delight Method annotation object.
 *
 * @Annotation
 */
class WrappersDelightMethod {
  
  const GETTER     = 'getter';
  const SETTER     = 'setter';
  const CONDITION  = 'condition';
  const SORT       = 'sort';
  const EXISTS     = 'exists';
  const NOT_EXISTS = 'not_exists';

  /**
   * The field.
   *
   * @var string
   */
  public $field;

  /**
   * Type
   * 
   * @var string
   */
  public $type;

}
