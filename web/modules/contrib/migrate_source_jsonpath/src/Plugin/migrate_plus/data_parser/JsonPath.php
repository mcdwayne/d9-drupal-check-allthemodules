<?php

namespace Drupal\migrate_source_jsonpath\Plugin\migrate_plus\data_parser;

use Drupal\migrate_plus\Plugin\migrate_plus\data_parser\Json;
use JsonPath\JsonObject;

/**
 * Obtain JSON data for migration using JSONPath.
 *
 * @DataParser(
 *   id = "jsonpath",
 *   title = @Translation("JSONPath")
 * )
 */
class JsonPath extends Json {

  /**
   * {@inheritdoc}
   */
  protected function getSourceData($url) {
    $response = $this->getDataFetcherPlugin()->getResponseContent($url);

    // Convert objects to associative arrays.
    $source_data = json_decode($response, TRUE);

    // Make query by JSON selector.
    $json = new JsonObject($source_data);
    $source_data = $json->get($this->itemSelector);

    return $source_data;
  }

  /**
   * {@inheritdoc}
   */
  protected function fetchNextRow() {
    $current = $this->iterator->current();
    if ($current) {
      foreach ($this->fieldSelectors() as $field_name => $selector) {
        $json = new JsonObject($current, TRUE);
        $field_value = $json->get($selector);

        // If selector returned single value in array.
        // Single value required to be a string for being able to use this field
        // as source primary key.
        if (is_array($field_value) && 1 == count($field_value)) {
          $field_value = reset($field_value);
        }

        $this->currentItem[$field_name] = $field_value;
      }
      if (!empty($this->configuration['include_raw_data'])) {
        $this->currentItem['raw'] = $current;
      }
      $this->iterator->next();
    }
  }

}
