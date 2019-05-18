<?php

namespace Drupal\janrain_connect_ui\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a login constraint annotation object.
 *
 * @Annotation
 */
class LoginConstraint extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The error message shown if the constraint fails.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $errorMessage;

}
