<?php

namespace Drupal\developer_suite\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Class Hook.
 *
 * @package Drupal\developer_suite\Annotation
 *
 * @Annotation
 */
class Hook extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The plugin label.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

}
