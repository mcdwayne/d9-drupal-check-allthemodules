<?php

namespace Drupal\field_formatters;

/**
 * An interface for Slug.
 */
interface ConvertSlugInterface {

  /**
   * Convert a text into slug using a specific separator.
   *
   * @return string
   *   The Slug text.
   */
  public function textIntoSlugSeparator($string, $separator);

}
