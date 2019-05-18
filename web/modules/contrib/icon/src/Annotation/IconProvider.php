<?php

namespace Drupal\icon\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a IconProvider annotation object.
 *
 * @Annotation
 */
class IconProvider extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name.
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * The url for more information regarding the provider
   *
   * @var string
   */
  public $url;

  /**
   * An array of settings passed to the renderer.
   *
   * @var array
   */
  public $settings = [];

}
