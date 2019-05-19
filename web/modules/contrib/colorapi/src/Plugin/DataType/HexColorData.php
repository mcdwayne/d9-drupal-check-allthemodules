<?php

namespace Drupal\colorapi\Plugin\DataType;

use Drupal\Core\TypedData\Plugin\DataType\StringData;

/**
 * Provides the Hexadecimal Color typed data type.
 *
 * This data type is a wrapper for hexadecimal color strings, in the format
 * #XXX or #XXXXXX, where X is a hexadecimal character (0-9, a-f).
 *
 * @DataType(
 *   id = "hexadecimal_color",
 *   label = @Translation("Hexadecimal Color"),
 *   constraints = {"HexadecimalColor" = {}}
 * )
 */
class HexColorData extends StringData implements HexColorInterface {

  /**
   * {@inheritdoc}
   */
  public function setValue($value, $notify = TRUE) {
    // Cast the value to upper case for consistency.
    $value = strtoupper($value);

    parent::setValue($value);
  }

}
