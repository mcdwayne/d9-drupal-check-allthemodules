<?php

namespace Drupal\comment_approver\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Comment approver item annotation object.
 *
 * @see \Drupal\comment_approver\Plugin\CommentApproverManager
 * @see plugin_api
 *
 * @Annotation
 */
class CommentApprover extends Plugin {


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

  /**
   * The description of the plugin
   *
   * @var string
   */
  public $description;

}
