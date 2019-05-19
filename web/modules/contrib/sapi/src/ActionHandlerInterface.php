<?php

namespace Drupal\sapi;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for Statistics action handler plugins.
 */
interface ActionHandlerInterface extends PluginInspectionInterface {

  /**
   * Process a SAPI action.
   *
   * This is the primary method whereby a handler is made aware of a statistics
   * action which can be tracked.  Passed in is the action definition, which
   * can be identified by type, on which the plugin can perform any tracking
   * action.
   *
   * @param \Drupal\sapi\ActionTypeInterface $item
   *
   */
  public function process(ActionTypeInterface $item);

}
