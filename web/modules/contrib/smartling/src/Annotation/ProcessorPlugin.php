<?php

/**
 * @file
 * Contains \Drupal\smartling\Annotation\SourcePlugin.
 */

namespace Drupal\smartling\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Source plugin annotation object.
 *
 * @Annotation
 *
 * @see \Drupal\smartling\ProcessorManager
 */
class ProcessorPlugin extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the source.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * A short description of the source.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $description;

  /**
   * The name of the source plugin class.
   *
   * This is not provided manually, it will be added by the discovery mechanism.
   *
   * @var string
   */
  public $class;

}
