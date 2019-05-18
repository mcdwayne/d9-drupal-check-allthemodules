<?php

declare(strict_types = 1);

namespace Drupal\field_autovalue\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Field Autovalue item annotation object.
 *
 * @see \Drupal\field_autovalue\Plugin\FieldAutovalueManager
 *
 * @Annotation
 */
class FieldAutovalue extends Plugin {

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
   * The field types this plugin should work with.
   *
   * @var array
   */
  // @codingStandardsIgnoreLine
  public $field_types = [];

}
