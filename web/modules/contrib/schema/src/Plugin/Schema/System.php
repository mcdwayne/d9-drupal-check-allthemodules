<?php
/**
 * @file
 * Contains Drupal\schema\Plugin\Schema\System.
 */

namespace Drupal\schema\Plugin\Schema;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\PluginBase;
use Drupal\schema\SchemaProviderInterface;

/**
 * Provides schema information defined by modules in implementations of
 * hook_schema().
 *
 * Note that we specifically do not use drupal_get_schema() here, because it
 * removes description keys which we want to keep so we can also detect changes
 * on table and column comments during comparison. The downside is that we have
 * to collect all schema information manually ourselves.
 *
 * @SchemaProvider(id = "system")
 */
class System extends PluginBase implements SchemaProviderInterface {

  const CACHE_BIN = 'schema_provider_system';

  /**
   * {@inheritdoc}
   */
  public function get($rebuild = FALSE) {
    //
    static $schema;

    if (!isset($schema) || $rebuild) {
      // Try to load the schema from cache.
      if (!$rebuild && $cached = \Drupal::cache()->get(self::CACHE_BIN)) {
        $schema = $cached->data;
      }
      // Otherwise, rebuild the schema cache.
      else {
        $schema = array();
        // Load the .install files to get hook_schema.
        \Drupal::moduleHandler()->loadAllIncludes('install');

        require_once DRUPAL_ROOT . '/core/includes/common.inc';
        // Invoke hook_schema for all modules.
        foreach (\Drupal::moduleHandler()
                   ->getImplementations('schema') as $module) {
          // Cast the result of hook_schema() to an array, as a NULL return value
          // would cause array_merge() to set the $schema variable to NULL as well.
          // That would break modules which use $schema further down the line.
          $current = (array) \Drupal::moduleHandler()
            ->invoke($module, 'schema');
          // Set 'module' and 'name' keys for each table.
          _drupal_schema_initialize($current, $module, FALSE);
          $schema = array_merge($schema, $current);
        }
        \Drupal::moduleHandler()->alter('schema', $schema);

        // If the schema is empty, avoid saving it: some database engines require
        // the schema to perform queries, and this could lead to infinite loops.
        if (!empty($schema)) {
          \Drupal::cache()->set(self::CACHE_BIN, $schema, Cache::PERMANENT);
        }
      }
    }

    return $schema;
  }
}
