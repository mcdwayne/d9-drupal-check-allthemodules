<?php

namespace Drupal\wsdata\Plugin\WSDecoder;

use Drupal\wsdata\Plugin\WSDecoderBase;

/**
 * XML Decoder.
 *
 * @WSDecoder(
 *   id = "WSDecoderXML",
 *   label = @Translation("XML Decoder", context = "WSDecoder"),
 * )
 */
class WSDecoderXML extends WSDecoderBase {

  /**
   * {@inheritdoc}
   *
   * Decode the web service response string.
   */
  public function decode($data) {
    if (!isset($data) || empty($data)) {
      return;
    }
    $data = trim($data);
    libxml_use_internal_errors(TRUE);
    try {
      $data = new \SimpleXMLElement($data);
      if ($data->count() == 0) {
        return [$data->getName() => $data->__toString()];
      }
      $data = get_object_vars($data);
      foreach ($data as $key => $value) {
        $data[$key] = $this->decodeXml($value);
      }
    }
    catch (exception $e) {
      return FALSE;
    }
    libxml_use_internal_errors(FALSE);
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function accepts() {
    return ['text/xml'];
  }

  /**
   * XML Parsing helper function, converts nested XML objects into arrays.
   */
  private function decodeXml($value) {
    if (is_object($value) and get_class($value)) {
      $value = get_object_vars($value);
      foreach ($value as $k => $v) {
        $value[$k] = $this->decodeXml($v);
      }
    }
    elseif (is_array($value)) {
      foreach ($value as $key => $xml) {
        $value[$key] = $this->decodeXml($xml);
      }
    }

    return $value;
  }

}
