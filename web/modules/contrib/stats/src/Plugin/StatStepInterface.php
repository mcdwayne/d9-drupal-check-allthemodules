<?php

namespace Drupal\stats\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\stats\RowCollection;

/**
 * Defines an interface for Stat process plugins.
 */
interface StatStepInterface extends PluginInspectionInterface {

  /**
   * Processes the given collection with the given plugin.
   *
   * @param \Drupal\stats\RowCollection $collection
   */
  public function process(RowCollection $collection);

}
