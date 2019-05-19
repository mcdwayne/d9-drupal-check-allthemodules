<?php

namespace Drupal\webform_score\Plugin;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Collects available webform score.
 */
interface WebformScoreManagerInterface extends PluginManagerInterface {

  /**
   * Get a list of WebformScore plugin options compatible with given data type.
   *
   * @param string $data_type_id
   *   Data type plugin ID for which to retrieve compatible WebformScore plugin
   *   options
   * @param bool $include_aggregation
   *   Whether to include aggregation plugins.
   *
   * @return array
   *   Array of key-value pairs that represents WebformScore plugins compatible
   *   with provided data type. Keys are WebformScore plugin IDs whereas values
   *   are labels of the corresponding WebformScore plugin.
   */
  public function pluginOptionsCompatibleWith($data_type_id, $include_aggregation = TRUE);

}
