<?php

namespace Drupal\timed_node_page\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Plugin annotation for timed node pages.
 *
 * @Annotation
 */
class TimedNodePage extends Plugin {

  /**
   * The bundle form which to select node.
   *
   * @var string
   */
  public $bundle;

  /**
   * The path of the page.
   *
   * @var string
   */
  public $path;

  /**
   * The start field on the bundle.
   *
   * @var string
   */
  public $startField;

  /**
   * The end field on the bundle.
   *
   * It's not required. In case it's not provided one node will be cached with
   * unlimited max age.
   *
   * @var string|null
   */
  public $endField = NULL;

  /**
   * Whether to use custom response.
   *
   * @var bool
   */
  public $usesCustomResponse = FALSE;

  /**
   * The priority of this plugin.
   *
   * @var int
   */
  public $priority = 1;

}
