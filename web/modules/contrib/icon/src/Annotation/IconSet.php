<?php

namespace Drupal\icon\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a IconSet annotation object.
 *
 * @Annotation
 */
class IconSet extends Plugin {

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
   * An array of icons grouped by unique name.
   *
   * @var array
   */
  public $icons = [];

  /**
   * The provider name corresponding to the icon set.
   *
   * @var string
   */
  public $provider;

  /**
   * The url for more information regarding the icon set.
   *
   * @var string
   */
  public $url;

  /**
   * Supplemental information for identifying the icon set.
   *
   * @var string
   */
  public $version;

  /**
   * Path where the icon set resource files are located.
   *
   * @var string
   */
  public $path;

  /**
   * The renderer the icon set should implement.
   *
   * @var string
   */
  public $renderer;

  /**
   * An array of settings passed to the renderer.
   *
   * @var array
   */
  public $settings = [];

  /**
   * An array of resources to be loaded alongside the icon set.
   *
   * @var array
   */
  public $attached = [];

}
