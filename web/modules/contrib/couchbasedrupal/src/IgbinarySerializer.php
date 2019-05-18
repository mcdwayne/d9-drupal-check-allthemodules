<?php

namespace Drupal\couchbasedrupal;

/**
 * Serializer that properly deals with
 * failed unserialization
 */
class IgbinarySerializer implements SerializerInterface {

  /**
   * Serialized FALSE
   *
   * @var string
   */
  protected $nullSerialized;

  /**
   * Get an instance of IgbinarySerializer
   */
  public function __construct() {
    $this->nullSerialized = igbinary_serialize(NULL);
  }

  /**
   * Serialize the data
   *
   * @param string $data
   *  The serialized data
   *
   * @return mixed
   */
  public function serialize($data) {
    return igbinary_serialize($data);
  }

  /**
   * Unserialize data
   * @param mixed $data
   *  The string to unserialize
   * @param bool $success
   *  True if unserialization was successful
   * @return mixed
   */
  public function unserialize($data, bool &$success) {
    $success = TRUE;
    $result = @igbinary_unserialize($data);
    if ($result === NULL) {
      if ($data == $this->nullSerialized) {
        return NULL;
      }
      $success = FALSE;
      return NULL;
    }
    return $result;
  }
}