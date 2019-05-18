<?php

namespace Drupal\image_styles_mapping\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Plugin annotation object.
 *
 * @ingroup plugin_api
 *
 * @Annotation
 */
class ImageStylesMapping extends Plugin {

  /**
   * The plugin id.
   *
   * @var string
   */
  public $id;

  /**
   * The plugin label.
   *
   * @var string
   */
  public $label;

  /**
   * The plugin description.
   *
   * @var string
   */
  public $description;

}
