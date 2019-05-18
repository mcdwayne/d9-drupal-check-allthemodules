<?php

namespace Drupal\icon\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a IconRenderer annotation object.
 *
 * @Annotation
 */
class IconRenderer extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name.
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * The file where the preprocessing and theming hooks are defined.
   *
   * @var string
   */
  public $file;

  /**
   * The path where the file is located.
   *
   * @var string
   */
  public $path;

}
