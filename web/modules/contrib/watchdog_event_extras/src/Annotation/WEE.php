<?php

namespace Drupal\watchdog_event_extras\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a WEE annotation object.
 *
 * @see \Drupal\watchdog_event_extras\WEEPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class WEE extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * Name of the WEE type.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title;

}
