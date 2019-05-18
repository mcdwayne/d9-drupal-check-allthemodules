<?php

namespace Drupal\colors\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Colors Scheme annotation object.
 *
 * @Annotation
 */
class ColorsScheme extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The name of the module that defines this plugin.
   *
   * @var string
   */
  public $module;

  /**
   * The plugin name.
   *
   * @var string
   */
  public $title;

  /**
   * The parent category for the plugin.
   *
   * @var string (optional)
   */
  public $parent = '';

  /**
   * The default child of a parent category.
   *
   * @var boolean (optional)
   */
  public $default = FALSE;

  /**
   * The human-readable name of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation (optional)
   *
   * @ingroup plugin_translatable
   */
  public $label = '';

  /**
   * A short description of the mail plugin.
   *
   * @var \Drupal\Core\Annotation\Translation (optional)
   *
   * @ingroup plugin_translatable
   */
  public $description = '';

  /**
   * The name of a callback function.
   *
   * @var string (optional)
   */
  public $callback = '';

  /**
   * The name of a callback function to retrieve multiple data.
   *
   * @var string (optional)
   */
  public $multiple = '';

  /**
   * A default weight for the scheme.
   *
   * @var int (optional)
   */
  public $weight = 0;

}
