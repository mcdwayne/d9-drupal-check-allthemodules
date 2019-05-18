<?php

namespace Drupal\select2boxes;

/**
 * Trait MinSearchLengthTrait.
 *
 * @package Drupal\select2boxes
 */
trait MinSearchLengthTrait {

  /**
   * Limit search input visibility by results length.
   *
   * @param array $attributes
   *   Element attributes array.
   */
  protected function limitSearchByMinLength(array &$attributes) {
    $config = \Drupal::config('select2boxes.settings');
    if ($config->get('limited_search') == '1') {
      $length = $config->get('minimum_search_length') ?: 0;
      $attributes['data-minimum-search-length'] = $length;
    }
  }

}
