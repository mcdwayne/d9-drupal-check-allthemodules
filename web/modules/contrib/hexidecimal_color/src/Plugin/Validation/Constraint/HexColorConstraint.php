<?php

namespace Drupal\hexidecimal_color\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Ensures that values are valid hexidecimal color strings.
 *
 * @Constraint(
 *   id = "hexidecimal_color",
 *   label = @Translation("Hexidecimal Color", context = "Validation"),
 *   type = "string"
 * )
 */
class HexColorConstraint extends Constraint {

  /**
   * The message shown when the value is not a valid hexidecimal color string.
   *
   * @var string
   */
  public $notValidHexidecimalColorString = '%value is not a valid hexidecimal color string. Hexidecimal color strings are in the format #XXXXXX where X is a hexidecimal character (0-9, a-f).';

}
