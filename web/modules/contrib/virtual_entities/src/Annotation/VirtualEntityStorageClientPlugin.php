<?php

namespace Drupal\virtual_entities\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Virtual entity storage client item annotation object.
 *
 * @see \Drupal\virtual_entities\Plugin\VirtualEntityStorageClientPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class VirtualEntityStorageClientPlugin extends Plugin {

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
