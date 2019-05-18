<?php

namespace Drupal\log_monitor\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Scheduler plugin item annotation object.
 *
 * @see \Drupal\log_monitor\Plugin\SchedulerPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class LogMonitorScheduler extends Plugin {


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
  public $title;

  /**
   * The description of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

}
