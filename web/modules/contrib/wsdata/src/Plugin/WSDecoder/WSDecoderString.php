<?php

namespace Drupal\wsdata\Plugin\WSDecoder;

use Drupal\wsdata\Plugin\WSDecoderBase;

/**
 * String Decoder.
 *
 * @WSDecoder(
 *   id = "WSDecoderString",
 *   label = @Translation("String Decoder (Passes data as is)", context = "WSDecoder"),
 * )
 */
class WSDecoderString extends WSDecoderBase {

  /**
   * {@inheritdoc}
   *
   * Return data as is.
   */
  public function decode($data) {
    return $data;
  }

}
