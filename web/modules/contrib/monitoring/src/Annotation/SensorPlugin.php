<?php

/**
 * @file
 * Contains \Drupal\monitoring\Annotation\SensorPlugin.
 */

namespace Drupal\monitoring\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines SensorPlugin annotation object for reference by SensorPlugin Plugins.
 *
 * @Annotation
 */
class SensorPlugin extends Plugin {

   /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation (optional)
   */
  public $description = '';

  /**
   * The provider of the annotated class.
   *
   * @var string
   */
  public $provider;

  /**
   * Whether plugin instances can be created or not.
   *
   * @var boolean
   */
  public $addable;
}
