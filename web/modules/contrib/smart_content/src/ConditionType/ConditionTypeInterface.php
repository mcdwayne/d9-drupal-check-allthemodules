<?php

namespace Drupal\smart_content\ConditionType;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for Smart condition type plugins.
 */
interface ConditionTypeInterface extends PluginInspectionInterface {

  // Returns data required for processing conditions.
  public function getAttachedSettings();

}
