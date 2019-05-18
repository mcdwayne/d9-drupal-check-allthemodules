<?php

namespace Drupal\git_issues\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines the git issues plugin annotation object.
 *
 * Plugin namespace: Plugin\GitIssues.
 *
 * @Annotation
 */
class GitIssuesPlugin extends Plugin {


  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The Git Manager label.
   *
   * Defaults to the main label.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $gitLabel;

}
