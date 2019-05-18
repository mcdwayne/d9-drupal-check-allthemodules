<?php

/**
 * @file
 * Contains \Drupal\quick_pages\Annotation\MainContent.
 */

namespace Drupal\quick_pages\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines main content annotation object.
 *
 * @Annotation
 */
class MainContent extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $title;

}
