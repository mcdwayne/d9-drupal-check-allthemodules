<?php

namespace Drupal\autotrader_csv\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Autotrader CSV Node Export item annotation object.
 *
 * @see \Drupal\autotrader_csv\Plugin\AutotraderCsvNodeExportManager
 * @see plugin_api
 *
 * @Annotation
 */
class AutotraderCsvNodeExport extends Plugin {


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
