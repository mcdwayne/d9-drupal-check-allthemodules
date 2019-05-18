<?php

namespace Drupal\scheduled_executable\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines the resolver plugin annotation object.
 *
 * @see plugin_api
 *
 * @Annotation
 */
class ScheduledExecutableResolver extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The plugin label.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

}
