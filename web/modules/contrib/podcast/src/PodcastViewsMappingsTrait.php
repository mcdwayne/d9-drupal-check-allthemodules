<?php

namespace Drupal\podcast;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Url;

/**
 * Trait that contains common mapping tasks for channel and item level mappings.
 */
trait PodcastViewsMappingsTrait {

  /**
   * Builds the channel element from the options.
   *
   * @param string $key
   *   The name of the key.
   * @param int $row_index
   *   The index of the row to get the fields from.
   * @param string[] $options_parents
   *   The parents to extract the field name from the config options.
   *
   * @return array
   *   Key value array for the property.
   */
  protected function buildElementFromOptions($key, $row_index = 0, array $options_parents = NULL) {
    if (empty($options_parents)) {
      $options_parents = [sprintf('%s_field', $key)];
    }
    $field_name = NestedArray::getValue($this->options, $options_parents);
    if (empty($field_name)) {
      return [];
    }
    $markup = $this->getField($row_index, $field_name);

    return $markup
      ? [
        'key' => $key,
        'value' => $markup,
      ]
      : [];
  }

  /**
   * Same as buildElementFromOptions but generates full URLs.
   *
   * @param string $key
   *   The name of the key.
   * @param int $row_index
   *   The index of the row to get the fields from.
   * @param string[] $options_parents
   *   The parents to extract the field name from the config options.
   *
   * @return array
   *   Key value array for the property.
   */
  protected function buildElementForLink($key, $row_index = 0, array $options_parents = NULL) {
    $build = $this->buildElementFromOptions($key, $row_index, $options_parents);
    if (empty($build['value'])) {
      return [];
    }
    // Make sure to cast to a string for the Url conversion.
    $value = (string) $build['value'];
    // Do not convert to an absolute URL if it already is one.
    if (preg_match('/https?:/', $value)) {
      return [
        'key' => $key,
        'value' => $value,
      ];
    }
    $link_input = '/' . ltrim($value, '/');
    try {
      return [
        'key' => $key,
        'value' => Url::fromUserInput($link_input)->setAbsolute()->toString(),
      ];
    }
    catch (\InvalidArgumentException $exception) {
      watchdog_exception('podcast', $exception);
    }
    return [];
  }

}
