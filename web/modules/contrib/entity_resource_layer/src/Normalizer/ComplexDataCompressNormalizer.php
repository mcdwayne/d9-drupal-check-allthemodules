<?php

namespace Drupal\entity_resource_layer\Normalizer;

use Drupal\serialization\Normalizer\ComplexDataNormalizer;

/**
 * Normalizer that removes redundant keys from values.
 *
 * @package Drupal\entity_resource_layer\Normalizer
 */
class ComplexDataCompressNormalizer extends ComplexDataNormalizer {

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []) {
    $attributes = parent::normalize($object, $format, $context);

    // If there is only one attribute then we don't need the array.
    if (count($attributes) == 1) {
      $attributes = array_pop($attributes);
    }

    return $attributes;
  }

}
