<?php

namespace Drupal\service_comment_count\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Comment service item annotation object.
 *
 * @see \Drupal\service_comment_count\CommentServiceManager
 * @see plugin_api
 *
 * @Annotation
 */
class CommentService extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

}
