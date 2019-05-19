<?php

namespace Drupal\stats\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\stats\RowCollection;

/**
 * Defines an interface for Stat source plugins.
 */
interface StatSourceInterface extends PluginInspectionInterface {

  /**
   * Provides collection of rows as a source.
   *
   * @return \Drupal\stats\RowCollection
   */
  public function getRows(): RowCollection;

}
