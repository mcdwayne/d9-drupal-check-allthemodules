<?php

namespace Drupal\aframe\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Class AFrameComponent.
 *
 * Plugin Namespace: Plugin\AFrame\Component
 *
 * @package Drupal\aframe\Annotation
 *
 * @Annotation
 */
class AFrameComponent extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the AFrame Component plugin.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

}
