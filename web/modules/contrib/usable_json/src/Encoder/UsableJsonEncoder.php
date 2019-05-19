<?php

namespace Drupal\usable_json\Encoder;

use Drupal\serialization\Encoder\JsonEncoder as SerializationJsonEncoder;

/**
 * Encodes Json API data.
 *
 * @internal
 */
class UsableJsonEncoder extends SerializationJsonEncoder {

  /**
   * The formats that this Encoder supports.
   *
   * @var string
   */
  protected static $format = ['usable_json'];

}
