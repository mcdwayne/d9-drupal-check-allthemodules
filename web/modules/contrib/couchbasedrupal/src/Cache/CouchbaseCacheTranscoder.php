<?php

namespace Drupal\couchbasedrupal\Cache;

use Drupal\couchbasedrupal\TranscoderInterface;
use Drupal\couchbasedrupal\SerializerFactory;

/**
 * Optimized transcoder for speed. Because we are using
 * couchbase only as a key value store, the data needs
 * not to be stored as a json document. Faster to serialize
 * and unserialize.
 *
 * Also, couchbase default transcoder
 * will try to store/retrieve our array
 * data as json documents effectively
 * transforming arrays to objects (or
 * the other way round) in a way difficult
 * to control.
 *
 */
class CouchbaseCacheTranscoder implements TranscoderInterface {

  /**
   * The serializer.
   *
   * @var \Drupal\couchbasedrupal\SerializerInterface
   */
  protected $serializer;

  /**
   * Get an instance of CouchbaseCacheTranscoder
   */
  public function __construct() {
    $factory = new SerializerFactory();
    $this->serializer = $factory->getSerializer();
  }

  /**
   * Encode a value
   *
   * @param mixed $value 
   * @return array
   */
  function encode($value) : array {
    $serialized = $this->serializer->serialize($value);
    return [$serialized, 0, 0];
  }

  /**
   * Decode a value
   *
   * @param string $bytes 
   * @param int $flags 
   * @param int $datatype 
   * @return mixed
   */
  function decode(string $bytes, int $flags, int $datatype) {
    $success = FALSE;
    $unserialized = $this->serializer->unserialize($bytes, $success);
    if (!$success) {
      return new CouchbaseCacheTranscoderBadDocument();
    }
    return $unserialized;
  }

}
