<?php

namespace Drupal\simple_amp\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines AMP Component annotation object.
 *
 * Plugin Namespace: Plugin\simple_amp\AmpComponent
 *
 * @see \Drupal\simple_amp\Plugin\AmpComponentManager
 * @see plugin_api
 *
 * @Annotation
 */
class AmpComponent extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The name of the codec.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $name;

  /**
   * Check if component should be included by default.
   *
   * @var boolean
   *
   */
  public $default = FALSE;

  /**
   * Regular expressions to match component
   *
   * @var array of regexp
   */
  public $regexp = [];

}
