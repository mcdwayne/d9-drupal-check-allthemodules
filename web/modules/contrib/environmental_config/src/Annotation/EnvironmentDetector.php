<?php

namespace Drupal\environmental_config\Annotation;

/**
 * @file
 * Contains environmental_config\Plugin\EnvironmentDetector.php.
 */

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a EnvironmentDetector item annotation object.
 *
 * @see \Drupal\environmental_config\EnvironmentDetectorManager.php
 * @see plugin_api
 *
 * @Annotation
 */
class EnvironmentDetector extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the action plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $name;

  /**
   * The human-readable name of the action plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

}
