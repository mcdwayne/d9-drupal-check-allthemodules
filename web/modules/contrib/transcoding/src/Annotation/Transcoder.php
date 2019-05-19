<?php

namespace Drupal\transcoding\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Transcoder annotation object.
 *
 * @see \Drupal\transcoding\Plugin\TranscoderManager
 * @see plugin_api
 *
 * @Annotation
 */
class Transcoder extends Plugin {

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
