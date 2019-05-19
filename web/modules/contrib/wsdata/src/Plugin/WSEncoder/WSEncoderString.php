<?php

namespace Drupal\wsdata\Plugin\WSEncoder;

use Drupal\wsdata\Plugin\WSEncoderBase;

/**
 * String Encoder.
 *
 * @WSEncoder(
 *   id = "WSEncoderString",
 *   label = @Translation("String Encoder (Passes data as is)", context = "WSEncoder"),
 * )
 */
class WSEncoderString extends WSEncoderBase {

  /**
   * Doesn't do anything.
   */
  public function encode(&$data, &$replacements, &$url) {
  }

}
