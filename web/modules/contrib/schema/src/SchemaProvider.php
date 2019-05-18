<?php
/**
 * @file
 * Contains Drupal\schema\SchemaProvider.
 */

namespace Drupal\schema;

use Drupal\Component\Annotation\Plugin;

/**
 * Annotation class for SchemaProvider plugins.
 *
 * @Annotation
 */
class SchemaProvider extends Plugin {
  /**
   * The schema provider plugin ID.
   *
   * @var string
   */
  public $id;
}
