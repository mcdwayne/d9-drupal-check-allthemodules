<?php

namespace Drupal\acquia_contenthub\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a FileSchemeHandler annotation object.
 *
 * @ingroup acquia_contenthub
 *
 * @Annotation
 */
class FileSchemeHandler extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human readable label of the scheme handler.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

}
