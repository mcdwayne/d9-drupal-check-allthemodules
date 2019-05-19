<?php

/**
 * @file
 * Contains \Drupal\smartling\Normalizer\FieldListNormalizer.
 */

namespace Drupal\smartling\Normalizer;

use Drupal\serialization\Normalizer\NormalizerBase;

/**
 * Converts list objects to arrays.
 *
 * Ordinarily, this would be handled automatically by Serializer, but since
 * there is a TypedDataNormalizer and the Field class extends TypedData, any
 * Field will be handled by that Normalizer instead of being traversed. This
 * class ensures that TypedData classes that also implement ListInterface are
 * traversed instead of simply returning getValue().
 */
class FieldListNormalizer extends NormalizerBase {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = 'Drupal\Core\Field\FieldItemListInterface';

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = array()) {
    $attributes = array();
    foreach ($object as $delta => $fieldItem) {
      $properties = $this->serializer->normalize($fieldItem, $format);
      // Exclude text format name.
      if (isset($properties['format'])) {
        unset($properties['format']);
      }
      // Exclude empty properties.
      $attributes[] = ['@delta' => $delta] + array_filter($properties);
    }
    return $attributes;
  }

}
