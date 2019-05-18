<?php

namespace Drupal\icons\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a IconLibrary annotation object.
 *
 * @ingroup icons
 *
 * @Annotation
 */
class IconLibrary extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The administrative label of the icon library.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label = '';

  /**
   * The description of the icon library.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description = '';

}
