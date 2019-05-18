<?php

namespace Drupal\kong\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Kong object item annotation object.
 *
 * @see \Drupal\kong\Plugin\KongObjectManager
 * @see plugin_api
 *
 * @Annotation
 */
class KongObject extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

}
