<?php

namespace Drupal\wsdata\Plugin\WSDecoder;

use Drupal\wsdata\Plugin\WSDecoderBase;
use Drupal\Component\Serialization\Json;

/**
 * JSON Decoder.
 *
 * @WSDecoder(
 *   id = "WSDecoderJSON",
 *   label = @Translation("JSON Decoder", context = "WSDecoder"),
 * )
 */
class WSDecoderJSON extends WSDecoderBase {

  /**
   * {@inheritdoc}
   */
  public function decode($data) {
    if (!isset($data) || empty($data)) {
      return;
    }

    // Remove UTF-8 BOM if present, json_decode() does not like it.
    if (substr($data, 0, 3) == pack("CCC", 0xEF, 0xBB, 0xBF)) {
      $data = substr($data, 3);
    }

    $data = trim($data);
    return Json::decode($data);
  }

  /**
   * {@inheritdoc}
   */
  public function accepts() {
    return ['text/json'];
  }

}
