<?php

namespace Drupal\chatbot_api\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Intent Plugin item annotation object.
 *
 * @see \Drupal\chatbot_api\Plugin\IntentPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class Intent extends Plugin {


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
