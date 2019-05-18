<?php

namespace Drupal\panels_extended\BlockConfig;

/**
 * Interface for adding custom config values to the JSON output of the block.
 */
interface JsonConfigurationInterface {

  /**
   * Get a list of configuration settings to add to the block JSON output.
   *
   * @return array
   *   A list of configuration settings to add to the block JSON output.
   */
  public function getConfigurationForJson();

}
