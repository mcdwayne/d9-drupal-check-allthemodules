<?php

namespace Drupal\healthz\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an healthz check annotation object.
 *
 * @see \Drupal\healthz\HealthzCheckPluginManager
 * @see \Drupal\healthz\Plugin\HealthzCheckInterface
 * @see \Drupal\healthz\Plugin\HealthzCheckBase
 * @see plugin_api
 *
 * @Annotation
 */
class HealthzCheck extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The name of the provider that owns the check.
   *
   * @var string
   */
  public $provider;

  /**
   * The human-readable name of the check.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title;

  /**
   * Additional administrative information about the check's behavior.
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
   * Whether this check is enabled or disabled by default.
   *
   * @var bool
   */
  public $status = FALSE;

  /**
   * The status code to return on failure.
   *
   * @var int
   */
  public $failureStatusCode = 500;

  /**
   * The default settings for the check.
   *
   * @var array
   */
  public $settings = [];

}
