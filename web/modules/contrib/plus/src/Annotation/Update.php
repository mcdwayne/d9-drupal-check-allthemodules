<?php

namespace Drupal\plus\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an Update annotation object.
 *
 * Plugin Namespace: "Plugin/Update".
 *
 * @see \Drupal\plus\UpdateInterface
 * @see \Drupal\plus\UpdateManagerProvider
 * @see plugin_api
 *
 * @Annotation
 *
 * @ingroup plugins_update
 */
class Update extends Plugin {

  /**
   * The schema version.
   *
   * @var int
   */
  public $id = '';

  /**
   * A short human-readable label.
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label = '';

  /**
   * A detailed description.
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $description = '';

  /**
   * Level of severity. Should be one of: default, danger, info, warning.
   *
   * @var string
   */
  public $severity = 'default';

  /**
   * Indicates whether or not the update should apply only to itself.
   *
   * Note: this only applies to sub-themes that of a base theme.
   *
   * @var bool
   */
  public $private = FALSE;

}
