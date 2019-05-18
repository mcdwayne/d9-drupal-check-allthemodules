<?php

namespace Drupal\events_logging;

/**
 * Interface StorageBackendInterface.
 */
interface StorageBackendInterface {

  /**
   * @param array $data
   *
   * @return mixed
   */
  public function save($data);

  public function deleteAll();

  public function delete($data);
}
