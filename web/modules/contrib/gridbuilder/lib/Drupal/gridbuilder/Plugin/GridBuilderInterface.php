<?php

/**
 * @file
 * Definition of Drupal\gridbuilder\Plugin\GridBuilderInterface.
 */

namespace Drupal\gridbuilder\Plugin;

/**
 * Defines the shared interface for all gridbuilder plugins.
 */
interface GridBuilderInterface {

  /**
   * Generates grid CSS for this grid system.
   *
   * @param (string) $wrapper_selector
   *   (optional) Wrapper CSS selector to use to scope the CSS.
   * @param (string) $col_selector_prefix
   *   (optional) Column selector prefix to scope the CSS.
   * @param (boolean) $skip_spacing
   *    Whether we should skip including spacing in the output. Useful for tight
   *    layout demonstration presentation.
   *
   * @return string
   *   Grid CSS for this grid system.
   */
  public function getGridCss($wrapper_selector = NULL, $col_selector_prefix = NULL, $skip_spacing = FALSE);
}
