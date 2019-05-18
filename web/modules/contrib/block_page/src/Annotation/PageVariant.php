<?php

/**
 * @file
 * Contains \Drupal\block_page\Annotation\PageVariant.
 */

namespace Drupal\block_page\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a page variant annotation object.
 *
 * @Annotation
 */
class PageVariant extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The administrative label.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $admin_label = '';

}
