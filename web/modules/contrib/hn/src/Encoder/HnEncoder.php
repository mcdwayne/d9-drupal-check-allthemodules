<?php

namespace Drupal\hn\Encoder;

use Drupal\serialization\Encoder\JsonEncoder;

/**
 * Encodes Headless Ninja data in JSON.
 *
 * Simply respond to hn format requests using the JSON encoder.
 */
class HnEncoder extends JsonEncoder {

  /**
   * {@inheritdoc}
   */
  protected static $format = ['hn'];

}
