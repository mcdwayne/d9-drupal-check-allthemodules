<?php

namespace Drupal\smart_content\VariationSetType;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for SmartVariationSetType plugins.
 */
interface VariationSetTypeInterface extends PluginInspectionInterface {

  /**
   * Returns a unique plugin ID representing the SmartVariationSetType plugin.
   *
   * @return string
   */
  public function getVariationPluginId();

  /**
   * Checks if the reaction is accessible and valid, eg. checking if the user
   * has access to view the reaction entity.
   *
   * @param $variation_id
   * @param $context
   *
   * @return mixed
   */
  public function validateReactionRequest($variation_id, $context);

}
