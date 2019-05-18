<?php

namespace Drupal\plugindecorator\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a generic plugin decorator item annotation object.
 *
 * @see \Drupal\plugindecorator\PluginDecoratorManager
 * @see plugin_api
 *
 * @Annotation
 */
class PluginDecorator extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The Interface FQN to be decorated.
   *
   * @var string
   *
   * @see \Drupal\plugindecorator\PluginDecoratorConfirmInterface
   */
  public $decorates;

}
