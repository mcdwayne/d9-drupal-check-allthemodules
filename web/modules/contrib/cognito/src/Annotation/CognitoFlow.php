<?php

namespace Drupal\cognito\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a cognito user flow.
 *
 * @Annotation
 */
class CognitoFlow extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name.
   *
   * @var string
   *
   * @ingroup plugin_translatable
   */
  public $label;

}
