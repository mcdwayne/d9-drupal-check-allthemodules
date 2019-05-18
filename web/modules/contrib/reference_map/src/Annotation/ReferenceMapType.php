<?php

namespace Drupal\reference_map\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Reference Map Type annotation object.
 *
 * @see \Drupal\reference_map\Plugin\ReferenceMapTypeManager
 * @see plugin_api
 *
 * @Annotation
 */
class ReferenceMapType extends Plugin {

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
  public $title;

  /**
   * The help text for the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $help;

}
