<?php

namespace Drupal\developer_suite\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Class Command.
 *
 * @package Drupal\developer_suite\Annotation
 *
 * @Annotation
 */
class CommandHandler extends Plugin {

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
