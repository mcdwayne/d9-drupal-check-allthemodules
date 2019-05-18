<?php

namespace Drupal\prepared_data\Serialization;

use Drupal\prepared_data\PreparedDataInterface;
use Drupal\prepared_data\PreparedData;

/**
 * Service component for handling JSON serialization of prepared data.
 */
class JsonSerializer implements SerializationInterface {

  /**
   * {@inheritdoc}
   */
  public function encode(PreparedDataInterface $prepared_data) {
    $encoded = '{}';
    if (!$prepared_data->isEmpty()) {
      $encoded = json_encode($prepared_data->data(), JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
    if (is_string($encoded)) {
      return $encoded;
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function decode($encoded_data) {
    $data_array = json_decode($encoded_data, TRUE);
    if (is_array($data_array)) {
      return new PreparedData($data_array);
    }
    return NULL;
  }

}
