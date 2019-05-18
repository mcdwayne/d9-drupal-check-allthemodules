<?php

namespace Drupal\hn\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Headless Ninja Entity Manager Plugin item annotation object.
 *
 * @see \Drupal\hn\Plugin\HnEntityManagerPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class HnEntityManagerPlugin extends Plugin {


  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

}
