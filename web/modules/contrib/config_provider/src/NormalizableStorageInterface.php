<?php

namespace Drupal\config_provider;

use Drupal\Core\Config\StorageInterface;

/**
 * Class for normalizing configuration on read.
 */
interface NormalizableStorageInterface extends StorageInterface {

  /**
   * Gets the value for whether data should be normalized on read.
   *
   * @return bool
   *   TRUE if data should be normalized on read. Otherwise, FALSE.
   */
  public function getNormalizeOnRead();

  /**
   * Sets the value for whether data should be normalized on read.
   *
   * @param bool $normalize_on_read
   *   TRUE if data should be normalized on read. Otherwise, FALSE.
   */
  public function setNormalizeOnRead($normalize_on_read);

}
