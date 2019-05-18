<?php

namespace Drupal\colorapi\Plugin\DataType;

use Drupal\Core\TypedData\Type\StringInterface;

/**
 * Interface for the Typed Data Hexadecimal Color Simple Data type.
 */
interface HexColorInterface extends StringInterface {

  /**
   * Regex used to validate hexadecimal color strings.
   *
   * @var string
   */
  const HEXADECIMAL_COLOR_REGEX = '/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/';

}
