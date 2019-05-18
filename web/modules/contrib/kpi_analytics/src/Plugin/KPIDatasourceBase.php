<?php

namespace Drupal\kpi_analytics\Plugin;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for KPI Datasource plugins.
 */
abstract class KPIDatasourceBase extends PluginBase implements KPIDatasourceInterface {

  /**
   * @inheritdoc
   */
  public function query($query) {
    $data = [];
    return $data;
  }
}
