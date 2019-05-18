<?php

namespace Drupal\flexfield\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Flexfield Type item annotation object.
 *
 * @see \Drupal\flexfield\Plugin\FlexFieldTypeManager
 * @see plugin_api
 *
 * @Annotation
 */
class FlexFieldType extends Plugin {


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
   * A short human readable description for the flexfield type.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

}
