<?php

namespace Drupal\user_restrictions\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines the annotation object for UserRestrictionType plugins.
 *
 * @see \Drupal\user_restrictions\Plugin\UserRestrictionTypeManager
 * @see plugin_api
 *
 * @Annotation
 */
class UserRestrictionType extends Plugin {

  /**
   * The plugin ID.

   * @var string
   */
  public $id;

  /**
   * The label of the plugin.

   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * Plugin weight.
   *
   * @var int
   */
  public $weight;

}
