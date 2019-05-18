<?php

namespace Drupal\field_encrypt\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining an EncryptedFieldValue entity.
 *
 * @ingroup field_encrypt
 */
interface EncryptedFieldValueInterface extends ContentEntityInterface {

  /**
   * Get the encrypted field value.
   *
   * @return string
   *   The encrypted value.
   */
  public function getEncryptedValue();

  /**
   * Set the encrypted field value.
   *
   * @param string $value
   *   The encrypted value.
   */
  public function setEncryptedValue($value);

}
