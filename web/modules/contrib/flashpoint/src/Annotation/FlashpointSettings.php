<?php

namespace Drupal\flashpoint\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a FlashpointSettings annotation object.
 *
 * Plugin Namespace: Plugin\flashpoint_settings
 *
 * @see plugin_api
 *
 * @Annotation
 */
class FlashpointSettings extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the FlashpointSettings.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * The category under which the FlashpointSettings should be listed in the UI.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $category;

}