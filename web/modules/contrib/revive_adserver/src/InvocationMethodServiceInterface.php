<?php

namespace Drupal\revive_adserver;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for Invocation method service plugins.
 */
interface InvocationMethodServiceInterface extends PluginInspectionInterface {

  /**
   * Returns the label of the invocation method.
   *
   * @return string
   *   The label of the invocation method.
   */
  public function getLabel();

  /**
   * Renders the revive zone for the current invocation method.
   *
   * @return array
   *   Render array for the built revive adserver zone.
   */
  public function render();

}
