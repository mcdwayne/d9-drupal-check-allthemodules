<?php

namespace Drupal\chunker\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an Chunker Method annotation object.
 *
 * Plugin Namespace: Plugin\ChunkerMethod
 *
 * @see \Drupal\chunker\ChunkerMethodInterface
 * @see \Drupal\chunker\ChunkerMethodBase
 * @see plugin_api
 *
 * @Annotation
 */
class ChunkerMethod extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the plugin.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * The human-readable description of the plugin.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $description;


  /**
   * Libraries to be attached when renderng this method.
   *
   * @var array (optional)
   */
  public $attached;

}
