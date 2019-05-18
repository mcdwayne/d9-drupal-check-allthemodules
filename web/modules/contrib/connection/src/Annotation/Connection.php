<?php

namespace Drupal\connection\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Connection item annotation object.
 *
 * @see \Drupal\connection\Plugin\ConnectionManager
 * @see plugin_api
 *
 * @Annotation
 */
class Connection extends Plugin {


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
