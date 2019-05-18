<?php

namespace Drupal\entity_sanitizer\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a FieldSanitizer annotation object.
 *
 * Sanitizers handle the database sanitation of field values. They are typically
 * instantiated and invoked by the FieldSanitizerManager object.
 *
 * @Annotation
 *
 * @see \Drupal\entity_sanitizer\FieldSanitizer\FieldSanitizerManager
 * @see \Drupal\entity_sanitizer\FieldSanitizer\FieldSanitizerInterface
 * @see plugin_api
 *
 * @ingroup entity_sanitizer
 */
class FieldSanitizer extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the sanitizer.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * The name of the field formatter class.
   *
   * This is not provided manually, it will be added by the discovery mechanism.
   *
   * @var string
   */
  public $class;

}
