<?php

namespace Drupal\packages\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Package item annotation object.
 *
 * @see \Drupal\packages\Plugin\PackageManager
 * @see plugin_api
 *
 * @Annotation
 */
class Package extends Plugin {

  /**
   * The package ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the package..
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
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

  /**
   * If the package should be enabled by default.
   *
   * @var bool
   */
  public $enabled = TRUE;

  /**
   * If the package is configurable.
   *
   * The package should implement settingsForm() if this is TRUE.
   *
   * @var bool
   */
  public $configurable = FALSE;

  /**
   * An optional additional permission required to use this package.
   *
   * @var string|FALSE
   */
  public $permission = FALSE;

  /**
   * The package default settings.
   *
   * If provided, these are usually editable by the user via settingsForm()
   * and by settings "configurable" to TRUE.
   *
   * @var array
   */
  public $default_settings = [];

}
