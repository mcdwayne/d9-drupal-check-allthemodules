<?php

namespace Drupal\cache_consistent\Cache;

use Drupal\transactionalphp\TransactionalPhpAwareTrait;
use Gielfeldt\TransactionalPHP\Indexer;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Drupal\Core\Cache\CacheBackendInterface;

/**
 * Provides a factory for creating cache consistent buffers.
 */
class CacheConsistentBufferFactory {

  use CacheTagsChecksumAwareTrait;
  use ContainerAwareTrait;
  use TransactionalPhpAwareTrait;

  /**
   * Returns a CacheBackend buffer object for Cache Consistent.
   *
   * @param string $bin
   *   The name of the cache bin.
   * @param CacheBackendInterface $cache_backend
   *   A cache backend.
   *
   * @return \Drupal\cache_consistent\Cache\CacheConsistentBufferInterface
   *   A Cache Consistent buffer.
   */
  public function get($bin, CacheBackendInterface $cache_backend) {
    $cache_buffer = new CacheConsistentBuffer($bin, $cache_backend, new Indexer($this->transactionalPhp), $this->checksumProvider);
    $cache_buffer->setContainer($this->container);
    return $cache_buffer;
  }

}
