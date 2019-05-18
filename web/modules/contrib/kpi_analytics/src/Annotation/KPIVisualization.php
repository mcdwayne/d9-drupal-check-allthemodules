<?php

namespace Drupal\kpi_analytics\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a KPI Visualization item annotation object.
 *
 * @see \Drupal\kpi_analytics\Plugin\KPIVisualizationManager
 * @see plugin_api
 *
 * @Annotation
 */
class KPIVisualization extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

}
