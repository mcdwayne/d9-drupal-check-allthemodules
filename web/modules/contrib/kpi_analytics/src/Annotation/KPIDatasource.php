<?php

namespace Drupal\kpi_analytics\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a KPI Datasource item annotation object.
 *
 * @see \Drupal\kpi_analytics\Plugin\KPIDatasourceManager
 * @see plugin_api
 *
 * @Annotation
 */
class KPIDatasource extends Plugin {

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
