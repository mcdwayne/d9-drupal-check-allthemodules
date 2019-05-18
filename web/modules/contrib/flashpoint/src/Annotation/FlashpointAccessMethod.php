<?php

namespace Drupal\flashpoint\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a FlashpointAccessMethod annotation object.
 *
 * Plugin Namespace: Plugin\flashpoint_access
 *
 * @see plugin_api
 *
 * @Annotation
 */
class FlashpointAccessMethod extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the FlashpointAccessMethod.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * The category under which the FlashpointAccessMethod should be listed in the UI.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $category;

}