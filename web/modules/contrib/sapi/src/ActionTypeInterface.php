<?php

namespace Drupal\sapi;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for Statistics action type plugins.
 */
interface ActionTypeInterface extends PluginInspectionInterface {

  /**
   * Describe yourself in one line
   */
  public function describe();

}
