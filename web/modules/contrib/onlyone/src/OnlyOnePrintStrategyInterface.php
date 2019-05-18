<?php

namespace Drupal\onlyone;

/**
 * Interface OnlyOnePrintStrategyInterface.
 */
interface OnlyOnePrintStrategyInterface {

  /**
   * Return a list of content types for print.
   *
   * @param object[] $content_types
   *   A multidimensional array of content types objects.
   *
   * @return array
   *   An array of content types to print keyed by content type machine name.
   */
  public function getContentTypesListForPrint(array $content_types);

}
