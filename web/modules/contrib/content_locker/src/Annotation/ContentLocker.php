<?php

namespace Drupal\content_locker\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Content Locker annotation object.
 *
 * @Annotation
 */
class ContentLocker extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the content locker plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

}
