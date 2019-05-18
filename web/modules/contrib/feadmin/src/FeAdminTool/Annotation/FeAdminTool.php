<?php

/**
 * @file
 * Contains \Drupal\feadmin\FeAdminTool\Annotation\FeAdminTool.
 * 
 * Sponsored by: www.freelance-drupal.com
 */

namespace Drupal\feadmin\FeAdminTool\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a FeAdminTool annotation object.
 *
 * @ingroup feadmin
 *
 * @Annotation
 */
class FeAdminTool extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the tool, displayed in the toolbar.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label = '';

  /**
   * The description of the tool, displayed in the toolbar and on admin page.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description = '';

}
