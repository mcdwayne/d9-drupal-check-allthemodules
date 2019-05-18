<?php

namespace Drupal\empty_fields\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a empty field annotation object.
 *
 * Plugin Namespace: Plugin\EmptyFields
 *
 * See working example \Drupal\empty_fields\Plugin\EmptyFields\EmptyFieldNbsp
 *
 * @see \Drupal\empty_fields\EmptyFieldPluginBase
 * @see \Drupal\empty_fields\EmptyFieldPluginInterface
 * @see \Drupal\empty_fields\EmptyFieldsPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class EmptyField extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The title of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title;

}
