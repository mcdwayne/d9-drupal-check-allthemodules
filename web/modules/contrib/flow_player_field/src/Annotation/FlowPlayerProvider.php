<?php

namespace Drupal\flow_player_field\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a FlowPlayerProvider item annotation object.
 *
 * @Annotation
 */
class FlowPlayerProvider extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The title of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title;

}
