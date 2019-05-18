<?php

namespace Drupal\memory_limit_policy\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a memory limit constraint annotation object.
 *
 * @Annotation
 */
class MemoryLimitConstraint extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the constraint type.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title;

  /**
   * The description shown to users.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

}
