<?php

/**
 * @file
 * Annotation for dream_fields.
 */

namespace Drupal\dream_fields\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a reusable form plugin annotation object.
 *
 * @Annotation
 */
class DreamField extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The weight of the plugin, as it should appear in the UI.
   *
   * @var int
   */
  public $weight = 0;

  /**
   * The name of the plugin.
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

  /**
   * The preview image.
   *
   * @var string
   */
  public $preview;

  /**
   * The module that contains the preview image.
   *
   * @var string
   */
  public $preview_provider;

  /**
   * The types of fields this plugin works with.
   *
   * @var array
   */
  public $field_types = [];

  /**
   * The module that contains the field.
   *
   * @var string
   */
  public $provider;

}
