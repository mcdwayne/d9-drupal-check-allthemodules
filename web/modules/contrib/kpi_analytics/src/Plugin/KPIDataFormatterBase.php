<?php

namespace Drupal\kpi_analytics\Plugin;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for KPI Data Formatter plugins.
 */
abstract class KPIDataFormatterBase extends PluginBase implements KPIDataFormatterInterface {

  /**
   * {@inheritdoc}
   */
  public function format(array $data) {
    return $data;
  }
}
