<?php

namespace Drupal\drd\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a DRD Update annotation object.
 *
 * @Annotation
 */
class Update extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The administrative label of the DRD Update.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $adminLabel;

}
