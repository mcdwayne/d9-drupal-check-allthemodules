<?php

/**
 * @file
 * Contains \Drupal\bideo\BideoPluginInterface.
 */

namespace Drupal\bideo;

/**
 * Defines an interface for bideo plugins.
 */
interface BideoPluginInterface {

  /**
   * Renders the output of a bideo plugin.
   */
  public function render();

}
