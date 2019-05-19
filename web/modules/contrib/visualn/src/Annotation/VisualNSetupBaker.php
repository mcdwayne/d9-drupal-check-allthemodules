<?php

namespace Drupal\visualn\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a VisualN Setup Baker item annotation object.
 *
 * @see \Drupal\visualn\Manager\SetupBakerManager
 * @see plugin_api
 *
 * @Annotation
 */
class VisualNSetupBaker extends Plugin {


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
