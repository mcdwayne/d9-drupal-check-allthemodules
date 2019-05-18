<?php

namespace Drupal\panels_extended\BlockConfig;

/**
 * Provides an interface to allow rendering a block for JSON output.
 */
interface JsonOutputInterface {

  /**
   * Builds the block output for JSON.
   *
   * @return array
   *   The block output for JSON.
   */
  public function buildForJson();

}
