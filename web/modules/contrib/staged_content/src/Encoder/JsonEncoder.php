<?php

namespace Drupal\staged_content\Encoder;

use Drupal\serialization\Encoder\JsonEncoder as SerializationJsonEncoder;

/**
 * Encodes entity storage data in JSON.
 *
 * Simply respond to stored_json format requests using the JSON encoder.
 * Useful for having flexible content for various entities to dogfeed into
 * distributions etc.
 */
class JsonEncoder extends SerializationJsonEncoder {

  /**
   * The formats that this Encoder supports.
   *
   * @var string
   */
  protected static $format = ['storage_json'];

}
