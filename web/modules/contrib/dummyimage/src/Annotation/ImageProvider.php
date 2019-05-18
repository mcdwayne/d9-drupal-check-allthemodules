<?php
/**
 * @file
 * Contains \Drupal\dummyimage\Annotation\ImageProvider
 */

namespace Drupal\dummyimage\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * @Annotation
 */
class ImageProvider extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The title of the image provider.
   *
   * The string should be wrapped in a @Translation().
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $title;

  public $url;

}
