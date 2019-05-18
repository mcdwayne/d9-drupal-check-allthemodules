<?php

namespace Drupal\chatbot_api_entities\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Push handler item annotation object.
 *
 * @see \Drupal\chatbot_api_entities\Plugin\PushHandlerManager
 * @see plugin_api
 *
 * @Annotation
 */
class PushHandler extends Plugin {


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
