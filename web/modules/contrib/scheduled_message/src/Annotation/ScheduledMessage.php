<?php

namespace Drupal\scheduled_message\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Scheduled message item annotation object.
 *
 * @see \Drupal\scheduled_message\Plugin\ScheduledMessageManager
 * @see plugin_api
 *
 * @Annotation
 */
class ScheduledMessage extends Plugin {


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
