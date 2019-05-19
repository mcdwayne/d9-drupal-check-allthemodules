<?php

namespace Drupal\wordfilter\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Wordfilter Process item annotation object.
 *
 * @see \Drupal\wordfilter\Plugin\WordfilterProcessManager
 * @see plugin_api
 *
 * @Annotation
 */
class WordfilterProcess extends Plugin {

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
   * The description of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;
}
