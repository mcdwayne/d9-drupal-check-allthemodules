<?php

namespace Drupal\crm_core_activity\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an activity type plugin annotation object.
 *
 * @Annotation
 */
class ActivityTypePlugin extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the type.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * A brief description of the plugin.
   *
   * This will be shown when adding or configuring this display.
   *
   * @var \Drupal\Core\Annotation\Translationoptional
   *
   * @ingroup plugin_translatable
   */
  public $description = '';

}
