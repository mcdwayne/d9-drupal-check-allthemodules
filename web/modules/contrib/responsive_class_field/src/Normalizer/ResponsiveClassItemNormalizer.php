<?php

namespace Drupal\responsive_class_field\Normalizer;

use Drupal\serialization\Normalizer\NormalizerBase;

/**
 * Convert the serialized responsive class field item to array structure.
 */
class ResponsiveClassItemNormalizer extends NormalizerBase {

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = 'Drupal\responsive_class_field\Plugin\Field\FieldType\ResponsiveClassItem';

  /**
   * {@inheritdoc}
   */
  public function normalize($field_item, $format = NULL, array $context = []) {
    $values = $field_item->getValue();

    $normalized['value'] = is_string($values['value']) ? unserialize($values['value']) : $values['value'];

    return $normalized;
  }

}
