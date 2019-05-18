<?php

namespace Drupal\revive_adserver\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Revive adserver invocation method item annotation object.
 *
 * @see \Drupal\revive_adserver\InvocationMethodServiceManager
 * @see plugin_api
 *
 * @Annotation
 */
class InvocationMethodService extends Plugin {

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

  /**
   * The weight of the plugin in its group.
   *
   * @var int
   */
  public $weight;

}
