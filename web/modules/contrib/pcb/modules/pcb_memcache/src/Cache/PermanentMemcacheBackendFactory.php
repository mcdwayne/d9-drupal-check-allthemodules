<?php

namespace Drupal\pcb_memcache\Cache;

use Drupal\memcache\MemcacheBackendFactory;

/**
 * Class PermanentMemcacheBackendFactory.
 */
class PermanentMemcacheBackendFactory extends MemcacheBackendFactory {

  /**
   * {@inheritdoc}
   */
  public function get($bin) {
    return new PermanentMemcacheBackend(
      $bin,
      $this->memcacheFactory->get($bin),
      $this->checksumProvider
    );
  }

}
