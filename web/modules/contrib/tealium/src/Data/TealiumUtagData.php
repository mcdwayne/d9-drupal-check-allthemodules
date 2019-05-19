<?php

namespace Drupal\tealium\Data;

/**
 * Class to define Tealium Universal Data Object (utag_data) name-value pairs.
 */
class TealiumUtagData extends UniversalDataObject implements TealiumUtagDataInterface {

  private $cleanVariableNameCache = [];

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    // Encode <, >, ', &, and " using the json_encode() options parameter.
    return json_encode(
      $this->getAllDataSourceValues(),
      JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getJavascriptVariables() {
    if (count($this->getAllDataSourceValues()) === 0) {
      return NULL;
    }
    else {
      return strval($this);
    }
  }

}
