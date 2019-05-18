<?php
/**
 * @file
 * Contains Drupal\schema\SchemaProviderInterface.
 */

namespace Drupal\schema;

interface SchemaProviderInterface {
  /**
   * @param bool $rebuild
   *   Whether to force a rebuild of schema data.
   *
   * @return array
   *   Array of schema information, keyed by table.
   */
  public function get($rebuild = FALSE);
}
