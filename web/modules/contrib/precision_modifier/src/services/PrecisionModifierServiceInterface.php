<?php

namespace Drupal\precision_modifier\services;


interface PrecisionModifierServiceInterface {

  /**
   * Increases the precision and scale of a decimal field. The number 123.4567
   * has a precision of 7 and a scale of 4.
   *
   * @param $field
   *The field whose precision and scale will be increased.
   * @param $bundle
   * The bundle the field is associated with
   * @param $precision
   * The precision
   * @param $scale
   * The scale and defaults to 0 if not defined
   *
   */
  public function increasePrecision($field, $bundle, $precision, $scale);
}