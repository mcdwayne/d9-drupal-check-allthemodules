<?php

namespace Drupal\select2boxes;

/**
 * Trait FlatteningOptionsTrait.
 *
 * @package Drupal\select2boxes
 */
trait FlatteningOptionsTrait {

  /**
   * Flattening multi-bundled options to prevent possible collisions.
   *
   * @param array &$options
   *   Options array.
   */
  protected function flatteningOptions(array &$options) {
    // Fix for multi-bundled options.
    $flat_options = [];
    foreach ($options as $key => $option) {
      if (is_array($option)) {
        $flat_options += $option;
        unset($options[$key]);
      }
    }
    $options += $flat_options;
  }

}
