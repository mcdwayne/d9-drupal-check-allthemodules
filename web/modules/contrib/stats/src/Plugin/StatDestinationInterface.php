<?php

namespace Drupal\stats\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\stats\Row;

/**
 * Defines an interface for Stat destination plugins.
 */
interface StatDestinationInterface extends PluginInspectionInterface {

  /**
   * Imports the given row to the destination.
   *
   * @param \Drupal\stats\Row $row
   *
   * @return mixed
   */
  public function import(Row $row);

}
