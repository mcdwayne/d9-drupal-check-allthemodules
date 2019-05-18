<?php

namespace Drupal\rewrite_field;

/**
 * Defines an interface for fetching transfrom case.
 */
interface TransformCasePluginInterface {

  /**
   * The transform method that will do the magic.
   *
   * @param string $output
   *   The output to be transformed.
   *
   * @return string
   *   Return transformed string.
   */
  public static function transform($output);

}
