<?php

/**
 * @file
 * Contains \Drupal\Core\Block\Annotation\Block.
 */

namespace Drupal\libraries_cdn\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Libraries CDN annotation object.
 *
 * @ingroup libraries_cdn
 *
 * @Annotation
 */
class LibrariesCdn extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;
}
