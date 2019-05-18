<?php

namespace Drupal\ad_entity\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines the annotation for Advertising context plugins.
 *
 * @see plugin_api
 *
 * @Annotation
 */
class AdContext extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the Advertising context.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The library which contains the JS implementation for this context plugin.
   *
   * @var string
   */
  public $library;

}
