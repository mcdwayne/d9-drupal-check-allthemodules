<?php

namespace Drupal\healthcheck\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Healthcheck plugin item annotation object.
 *
 * @see \Drupal\healthcheck\Plugin\HealthcheckPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class Healthcheck extends Plugin {


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

  /**
   * An array of string tags used to categorize the check.
   *
   * @var array
   */
  public $tags;

  /**
   * A description of the check performed.
   *
   * @var string
   */
  public $description;

}
