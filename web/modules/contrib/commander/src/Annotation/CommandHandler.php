<?php

namespace Drupal\commander\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Command handler item annotation object.
 *
 * @see \Drupal\commander\Plugin\CommandHandlerManager
 * @see plugin_api
 *
 * @Annotation
 */
class CommandHandler extends Plugin {


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
