<?php

namespace Drupal\kpi_analytics\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a KPI Data Formatter item annotation object.
 *
 * @see \Drupal\kpi_analytics\Plugin\KPIDataFormatterManager
 * @see plugin_api
 *
 * @Annotation
 */
class KPIDataFormatter extends Plugin {

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
