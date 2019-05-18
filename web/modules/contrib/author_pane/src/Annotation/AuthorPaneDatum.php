<?php
/**
 * @file
 * Contains the \Drupal\author_pane\Annotation\AuthorPaneDatum annotation plugin.
 */

namespace Drupal\author_pane\Annotation;

use Drupal\Component\Annotation\Plugin;


/**
 * Defines a AuthorPaneDatum annotation object.
 *
 * @Annotation
 */
class AuthorPaneDatum extends Plugin {
  /**
   * Machine name of the plugin.
   *
   * @var string
   */
  protected $id;

  /**
   * Title of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  protected $label;

  /**
   * A longer explanation of what the plugin is for.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  protected $description;

}