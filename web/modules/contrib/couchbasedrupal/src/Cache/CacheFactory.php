<?php

namespace Drupal\couchbasedrupal\Cache;

/**
 * Custom override of cache factory
 * to allow overriding default backends to use
 * couchbase instead of database.
 */
class CacheFactory extends \Drupal\Core\Cache\CacheFactory {

  /**
   * List of binaries consumed
   * by the couchbase backend.
   *
   * @var string[]
   */
  protected $used_binaries = [];

  /**
   * Get the service name for an associated binary.

   * @param string $bin
   * @return string
   */
  protected function getBinaryService($bin) : string {
    $cache_settings = $this->settings->get('cache');
    // First, look for a cache bin specific setting.
    if (isset($cache_settings['bins'][$bin])) {
      $service_name = $cache_settings['bins'][$bin];
    }
    // Second, use the default backend specified by the cache bin.
    elseif (isset($this->defaultBinBackends[$bin])) {
      $service_name = $this->defaultBinBackends[$bin];
    }
    // Third, use configured default backend.
    elseif (isset($cache_settings['default'])) {
      $service_name = $cache_settings['default'];
    }
    else {
      // Fall back to the couchbase backend if nothing else is configured.
      $service_name = 'cache.backend.couchbase';
    }
    return $service_name;
  }

  /**
   * {@inhertidoc}
   */
  public function get($bin) {
    $service_name = $this->getBinaryService($bin);
    return $this->container->get($service_name)->get($bin);
  }

  /**
   * Delete unused cache database storage.
   */
  public function clearUnusedDbBinaries() {
    // Some cache binaries might be stored in DB
    // using the streamlined bootstrap container...
    $protected_binaries = ['container'];
    $schema = \Drupal::database()->schema();
    $tables = $schema->findTables('cache_%');
    foreach ($tables as $table) {
      $bin = str_replace('cache_', '', $table);
      if (in_array($bin, $protected_binaries)) {
        continue;
      }
      $service = $this->getBinaryService($bin);
      if ($service == 'cache.backend.couchbase') {
        $schema->dropTable($table);
      }
    }
  }
}