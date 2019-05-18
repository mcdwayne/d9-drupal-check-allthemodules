<?php

namespace Drupal\colorapi\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Ensures that values are valid hexadecimal color strings.
 *
 * @Constraint(
 *   id = "HexadecimalColor",
 *   label = @Translation("Hexadecimal Color", context = "Validation"),
 *   type = "hexadecimal_color"
 * )
 */
class HexColorConstraint extends Constraint {

  /**
   * The message shown when the value is not a valid hexadecimal color string.
   *
   * @var string
   */
  public $notValidHexadecimalColorString = '%value is not a valid hexadecimal color string. Hexadecimal color strings are in the format #XXXXXX where X is a hexadecimal character (0-9, a-f).';

}
