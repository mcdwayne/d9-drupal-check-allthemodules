<?php

namespace Drupal\pcb\Cache;

use Drupal\Core\Cache\DatabaseBackendFactory;

/**
 * Class PermanentDatabaseBackendFactory.
 */
class PermanentDatabaseBackendFactory extends DatabaseBackendFactory {

  /**
   * {@inheritdoc}
   */
  public function get($bin) {
    return new PermanentDatabaseBackend($this->connection, $this->checksumProvider, $bin);
  }

}
