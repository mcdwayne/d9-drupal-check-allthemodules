<?php

namespace Drupal\charts\Plugin\chart;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for Chart plugins.
 */
interface ChartInterface extends PluginInspectionInterface {

  /**
   * Build Variables.
   *
   * @return array
   *   Variables.
   */
  public function buildVariables($options, $categories, $seriesData, $attachmentDisplayOptions, &$variables, $chartId);

  /**
   * Return the name of the chart.
   *
   * @return string
   *   Returns the name as a string.
   */
  public function getChartName();

}
