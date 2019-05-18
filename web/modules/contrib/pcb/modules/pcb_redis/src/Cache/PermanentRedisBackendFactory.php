<?php

namespace Drupal\pcb_redis\Cache;

use Drupal\redis\Cache\CacheBackendFactory;

/**
 * Class PermanentMemcacheBackendFactory.
 */
class PermanentRedisBackendFactory extends CacheBackendFactory {

  /**
   * {@inheritdoc}
   */
  public function get($bin) {
    return new PermanentRedisBackend($bin, $this->clientFactory->getClient(), $this->checksumProvider, $this->serializer);
  }

}
