<?php

namespace Drupal\prometheus_exporter\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an metrics collector annotation object.
 *
 * @see \Drupal\prometheus_exporter\MetricsCollectorPluginManager
 * @see \Drupal\prometheus_exporter\MetricsCollectorInterface
 * @see plugin_api
 *
 * @Annotation
 */
class MetricsCollector extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The name of the provider that owns the collector.
   *
   * @var string
   */
  public $provider;

  /**
   * The human-readable name of the collector.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title;

  /**
   * Additional administrative information about the collector's behavior.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description = '';

  /**
   * A default weight for the filter in new text formats.
   *
   * @var int
   */
  public $weight = 0;

  /**
   * Whether this collector is enabled or disabled by default.
   *
   * @var bool
   */
  public $enabled = TRUE;

  /**
   * The default settings for the collector.
   *
   * @var array
   */
  public $settings = [];

}
