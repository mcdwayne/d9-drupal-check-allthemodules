<?php

namespace Drupal\simple_a_b\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a report type.
 *
 * Plugin Namespace: Plugin\simple_a_b\SimpleABReport.
 *
 * @see \Drupal\simple_a_b\SimpleABReportManger
 * @see plugin_api
 *
 * @Annotation
 */
class SimpleABReport extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The name of the test type.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $name;


  /**
   * The method call.
   *
   * @var string
   */
  public $method;

}
