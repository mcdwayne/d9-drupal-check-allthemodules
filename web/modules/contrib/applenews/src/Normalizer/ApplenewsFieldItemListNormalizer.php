<?php

namespace Drupal\applenews\Normalizer;

use Drupal\Core\Field\FieldItemListInterface;

/**
 * Class ApplenewsFieldItemListNormalizer.
 *
 * @package Drupal\applenews\Normalizer
 */
class ApplenewsFieldItemListNormalizer extends ApplenewsNormalizerBase {

  /**
   * {@inheritdoc}
   */
  public function supportsNormalization($data, $format = NULL) {
    return $format === $this->format && $data instanceof FieldItemListInterface;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($field_item, $format = NULL, array $context = []) {
    $property = $context['field_property'];
    if ($property == 'base') {
      $value = $field_item->value;
    }
    else {
      $value = $this->serializer->normalize($field_item->{$property});
    }

    return $value;
  }

}
