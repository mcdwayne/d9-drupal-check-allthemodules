<?php

namespace Drupal\video_sitemap\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a VideoLocation item annotation object.
 *
 * @Annotation
 */
class VideoLocation extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The title of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title;

}
