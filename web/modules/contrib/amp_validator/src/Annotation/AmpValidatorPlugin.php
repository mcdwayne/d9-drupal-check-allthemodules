<?php

/**
 * @file
 * Provides ValidatorPlugin base class.
 */

namespace Drupal\amp_validator\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Plugin annotation object for AmpValidator plugins.
 *
 * Plugin Namespace: Plugin\AmpValidator
 *
 * @see \Drupal\amp_validator\Plugin\AmpValidatorPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class AmpValidatorPlugin  extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the plugin.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * The description of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

}
