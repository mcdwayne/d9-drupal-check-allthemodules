<?php

namespace Drupal\smart_content\Condition;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for Smart condition plugins.
 */
interface ConditionInterface extends PluginInspectionInterface {

  public function writeChangesToConfiguration();

  public function getAttachedSettings();

}
