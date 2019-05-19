<?php

namespace Drupal\hexidecimal_color\Plugin\DataType;

/**
 * Interface for the Hexidecimal Color Typed Data type.
 */
interface HexColorInterface {

  /**
   * Regex used to validate hexidecimal color strings.
   *
   * @var string
   */
  const HEXIDECIMAL_COLOR_REGEX = '/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/';

}
