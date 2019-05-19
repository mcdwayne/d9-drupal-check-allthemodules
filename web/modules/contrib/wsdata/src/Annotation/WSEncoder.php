<?php

namespace Drupal\wsdata\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Web Service Encoder item annotation object.
 *
 * @see \Drupal\wsdata\Plugin\WSEncoderManager
 * @see plugin_api
 *
 * @Annotation
 */
class WSEncoder extends Plugin {


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
