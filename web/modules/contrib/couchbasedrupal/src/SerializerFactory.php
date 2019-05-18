<?php

namespace Drupal\couchbasedrupal;

/**
 * Serializer that properly deals with
 * failed unserialization
 */
class SerializerFactory {

  /**
   * The serializer
   *
   * @var SerializerInterface
   */
  protected $serializer;

  /**
   * Get an instance of SerializerFactory.
   */
  public function __construct() {
    if(function_exists('igbinary_serialize')) {
      $this->serializer = new \Drupal\couchbasedrupal\IgbinarySerializer();
    }
    else {
      $this->serializer = new \Drupal\couchbasedrupal\PhpSerializer();
    }
  }

  /**
   * Grab the most performant available serializer.
   *
   * @return SerializerInterface
   */
  public function getSerializer() {
    return $this->serializer;
  }

}