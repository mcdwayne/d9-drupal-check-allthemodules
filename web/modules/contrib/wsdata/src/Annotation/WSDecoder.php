<?php

namespace Drupal\wsdata\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a WSDecoder plugin item annotation object.
 *
 * @see \Drupal\wsdata\Plugin\WSDecoderPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class WSDecoder extends Plugin {


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
