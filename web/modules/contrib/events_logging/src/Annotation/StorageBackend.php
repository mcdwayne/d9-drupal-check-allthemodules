<?php

namespace Drupal\events_logging\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a StorageBackend annotation object.
 *
 * @Annotation
 */
class StorageBackend extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the StorageBackend type.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * A short description of the StorageBackend type.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $description;

}
