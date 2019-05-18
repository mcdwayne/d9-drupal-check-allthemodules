<?php

namespace Drupal\bueditor\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a BUEditorPlugin annotation object.
 *
 * Plugin Namespace: Plugin\BUEditorPlugin
 *
 * @see \Drupal\bueditor\BUEditorPluginBase
 *
 * @Annotation
 */
class BUEditorPlugin extends Plugin {

  /**
   * Plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * Plugin label.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * Plugin weight.
   *
   * @var int
   */
  public $weight = 0;

}
