<?php
/**
 * @file
 * Contains Drupal\schema\Plugin\Schema\Caching.
 */

namespace Drupal\schema\Plugin\Schema;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\ChainedFastBackend;
use Drupal\Core\Cache\DatabaseBackend;
use Drupal\Core\Plugin\PluginBase;
use Drupal\schema\SchemaProviderInterface;
use ReflectionClass;

/**
 * Provides schema information for database cache tables.
 *
 * @SchemaProvider(id = "caching")
 */
class Caching extends PluginBase implements SchemaProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function get($rebuild = FALSE) {
    $complete_schema = array();
    $cache_bins = Cache::getBins();
    foreach ($cache_bins as $name => $cache) {
      // If we have a chained backend, check if the persistent backend is a
      // DatabaseBackend.
      if ($cache instanceof ChainedFastBackend) {
        $reflection = new ReflectionClass(get_class($cache));
        $property = $reflection->getProperty('consistentBackend');
        $property->setAccessible(TRUE);
        $cache = $property->getValue($cache);
      }
      // If we have a DatabaseBackend, add it's schema information.
      if ($cache instanceof DatabaseBackend) {
        $schema = $cache->schemaDefinition();
        $complete_schema['cache_' . $name] = $schema;
      }
    }
    return $complete_schema;
  }
}
