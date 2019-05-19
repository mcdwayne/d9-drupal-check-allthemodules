<?php

namespace Drupal\visualn\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a VisualN Resource Provider item annotation object.
 *
 * @see \Drupal\visualn\Manager\ResourceProviderManager
 * @see plugin_api
 *
 * @Annotation
 */
class VisualNResourceProvider extends Plugin {


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
