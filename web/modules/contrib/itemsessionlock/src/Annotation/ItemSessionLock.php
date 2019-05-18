<?php

namespace Drupal\itemsessionlock\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a lock annotation object.
 *
 * Plugin Namespace: Plugin\itemsessionlock\ItemSessionLock
 *
 * @see \Drupal\itemsessionlock\Plugin\ItemSessionLock\ItemSessionLockManager
 * @see plugin_api
 *
 * @Annotation
 */
class ItemSessionLock extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The lock label.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

}
