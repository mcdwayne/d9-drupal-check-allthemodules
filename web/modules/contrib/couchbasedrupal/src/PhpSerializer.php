<?php

namespace Drupal\couchbasedrupal;

/**
 * Serializer that properly deals with
 * failed unserialization
 */
class PhpSerializer implements SerializerInterface {

  /**
   * Serialized FALSE
   *
   * @var string
   */
  protected $falseSerialized;

  /**
   * Get an instance of PhpSerializer()
   */
  public function __construct() {
    $this->falseSerialized = serialize(FALSE);
  }

  /**
   * Summary of serialize
   * @param mixed $data
   * @return string
   */
  public function serialize($data) {
    return serialize($data);
  }

  /**
   * Unserialize
   *
   * @param string $data
   *   The serialized data.
   * @param bool $success
   *   True if data was correctly unserialized.
   * @return mixed
   */
  public function unserialize($data, bool &$success) {
    $success = TRUE;
    $result = @unserialize($data);
    if ($result === FALSE) {
      if ($data == $this->falseSerialized) {
        return FALSE;
      }
      $success = FALSE;
      return NULL;
    }
    return $result;
  }
}