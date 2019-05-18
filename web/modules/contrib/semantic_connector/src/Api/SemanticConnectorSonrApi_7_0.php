<?php

namespace Drupal\semantic_connector\Api;

/**
 * Class SemanticConnectorSonrApi_7_0
 *
 * API Class for the version 7.0.
 */
class SemanticConnectorSonrApi_7_0 extends SemanticConnectorSonrApi_6_1 {
  /**
   * Changes the result array so that it is compatible with older version.
   *
   * @param array $result
   *   The result of the search API call.
   *
   * @return array
   *   The compatible result for older version.
   */
  protected function makeSearchCompatible($result) {
    $result['results'] = $result['result'];
    unset($result['result']);

    return parent::makeSearchCompatible($result);
  }

  /**
   * Changes the result array so that it is compatible with older version.
   *
   * @param array $result
   *   The result of the search API call.
   *
   * @return array
   *   The compatible result for older version.
   */
  protected function makeSuggestCompatible($result) {
    $result['results'] = $result['result'];
    unset($result['result']);

    return parent::makeSuggestCompatible($result);
  }
}