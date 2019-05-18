<?php

namespace Drupal\fz152\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Fz152 annotation object.
 *
 * Fz152 forms are used to define a list of forms to provide confirmation.
 *
 * Plugin namespace: Plugin\Fz152
 *
 * @see \Drupal\fz152\Fz152PluginInterface
 * @see \Drupal\fz152\Fz152PluginBase
 * @see \Drupal\fz152\Fz152PluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class Fz152 extends Plugin {

  /**
   * Plugin machine name.
   *
   * @var string
   */
  public $id;

}
