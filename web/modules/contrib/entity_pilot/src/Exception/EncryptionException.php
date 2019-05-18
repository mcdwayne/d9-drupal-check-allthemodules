<?php

namespace Drupal\entity_pilot\Exception;

/**
 * Defines a class for encryption errors.
 */
class EncryptionException extends \Exception {

  /**
   * UUID of entity that caused exception.
   *
   * @var string
   */
  protected $uuid;

  /**
   * Factory method..
   *
   * @param \Exception $e
   *   Previous exception.
   * @param string $uuid
   *   UUID.
   *
   * @return \Drupal\entity_pilot\Exception\EncryptionException
   *   New exception.
   */
  public static function forUuid(\Exception $e, $uuid) {
    $exception = new static($e->getMessage(), $e->getCode(), $e);
    $exception->uuid = $uuid;
    return $exception;
  }

  /**
   * Gets value of Uuid.
   *
   * @return string
   *   Value of Uuid.
   */
  public function getUuid() {
    return $this->uuid;
  }

}
