<?php

namespace Drupal\kpi_analytics\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for KPI Datasource plugins.
 */
interface KPIDatasourceInterface extends PluginInspectionInterface {

  /**
   * Query the datasource
   */
  public function query($query);
}
