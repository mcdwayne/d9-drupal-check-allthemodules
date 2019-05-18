<?php
/**
 * Created by PhpStorm.
 * User: twhiston
 * Date: 09.02.17
 * Time: 16:26
 */

namespace Drupal\elastic_search\Plugin\FieldMapper\FormHelper;

trait AnalyzerDslProcessor {

  protected static $keys = [
    ['analyzer_language_deriver', 'analyzer'],
    ['search_analyzer_language_deriver', 'search_analyzer'],
    ['search_quote_analyzer_language_deriver', 'search_quote_analyzer'],
  ];

  /**
   * @param array $data
   *
   * @return array
   */
  protected function buildAnalyzerDsl(array $data): array {
    foreach (self::$keys as $key) {

      if (array_key_exists($key[0], $data)) {
        if ($data[$key[0]] === TRUE) {
          //Deriver is turned on
          $data[$key[1]] = '[elastic_search:language_derived_analyzer]';
        }
        unset($data[$key[0]]);
      }
      if (array_key_exists($key[1], $data)) {
        if ($data[$key[1]] === AnalyzerField::$NONE_IDENTIFIER) {
          unset($data[$key[1]]);
        }
      }
    }

    return $data;
  }

}