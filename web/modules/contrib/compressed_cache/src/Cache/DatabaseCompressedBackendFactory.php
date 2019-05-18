<?php

namespace Drupal\compressed_cache\Cache;

use Drupal\Core\Cache\DatabaseBackendFactory;

class DatabaseCompressedBackendFactory extends DatabaseBackendFactory {

  /**
   * Gets DatabaseBackend for the specified cache bin.
   *
   * @param $bin
   *   The cache bin for which the object is created.
   *
   * @return \Drupal\Core\Cache\DatabaseBackend
   *   The cache backend object for the specified cache bin.
   */
  public function get($bin) {
    $max_rows = $this->getMaxRowsForBin($bin);

    $_settings = [
      'cache_compression_ratio' => 6,
      'cache_compression_size_threshold' => 100,
      'garbage_collection_enabled' => TRUE,
    ];

    $_settings = array_merge($_settings, $this->settings->get('compressed_cache', []));

    return new DatabaseCompressedBackend($this->connection, $this->checksumProvider, $bin, $max_rows, $_settings['cache_compression_ratio'], $_settings['cache_compression_size_threshold'], $_settings['garbage_collection_enabled']);
  }

}
