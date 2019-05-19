<?php

namespace Drupal\kpi_analytics\Plugin;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for KPI Visualization plugins.
 */
abstract class KPIVisualizationBase extends PluginBase implements KPIVisualizationInterface {

  /**
   * Contains a list with labels for chart.
   *
   * @var array
   */
  protected $labels = [];

  /**
   * Contains a list with colors for chart.
   *
   * @var array
   */
  protected $colors = [];

  /**
   * {@inheritdoc}
   */
  public function render(array $data) {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function setLabels(array $labels) {
    $this->labels = $labels;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setColors(array $colors) {
    $this->colors = $colors;

    return $this;
  }

}
