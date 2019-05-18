<?php

namespace Drupal\drd\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a DRD Auth item annotation object.
 *
 * @see \Drupal\drd\Plugin\Auth\Manager
 * @see plugin_api
 *
 * @Annotation
 */
class Auth extends Plugin {

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
