<?php

namespace Drupal\drd;

use Drupal\Core\Entity\EntityInterface;

/**
 * Interface for DRD entities that have encrypted values.
 */
interface EncryptionEntityInterface extends EntityInterface {

  /**
   * Get a list of encrypted field names.
   *
   * @return array
   *   List of field names that are encrypted.
   */
  public function getEncryptedFieldNames();

}
