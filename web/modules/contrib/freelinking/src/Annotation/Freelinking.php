<?php

namespace Drupal\freelinking\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a freelinking plugin annotation object.
 *
 * Plugin namespace: Plugin\freelinking.
 *
 * @Annotation
 */
class Freelinking extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The plugin provider.
   *
   * @var string
   */
  public $provider;

  /**
   * The human-readable name of the freelinking plugin.
   *
   * This is used as an administrative summary in the freelinking filter
   * settings.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $title;

  /**
   * The plugin configuration.
   *
   * @var array
   */
  public $settings = [];

  /**
   * Describes whether or not the plugin can be disabled.
   *
   * @var bool
   */
  public $hidden = FALSE;

}
