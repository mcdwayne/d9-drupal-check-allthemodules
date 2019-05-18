<?php

namespace Drupal\entity_resource_layer\Normalizer;

use Drupal\Core\Field\Entity\BaseFieldOverride;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\serialization\Normalizer\ListNormalizer;

/**
 * Normalizer that depending on field cardinality sets the value.
 *
 * @package Drupal\entity_resource_layer\Normalizer
 */
class ListCompressNormalizer extends ListNormalizer {

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []) {
    $attributes = [];

    if ($this->getFieldCardinality($object) == 1) {
      // If the field is not multiple then there is no point having it in
      // array. It only removes code visibility at the consumer.
      $attributes = $this->serializer->normalize($object[0], $format, $context);
    }
    else {
      foreach ($object as $fieldItem) {
        $attributes[] = $this->serializer->normalize($fieldItem, $format, $context);
      }
    }

    return $attributes;
  }

  /**
   * Gets the cardinality of the field from the item list.
   *
   * @param object $object
   *   The list item.
   *
   * @return int|null
   *   The cardinality if it could be determined.
   */
  protected function getFieldCardinality($object) {
    $fieldDefinition = $object->getFieldDefinition();
    if ($fieldDefinition instanceof FieldConfig || $fieldDefinition instanceof BaseFieldOverride) {
      $fieldDefinition = $fieldDefinition->getFieldStorageDefinition();
    }

    if ($fieldDefinition instanceof FieldStorageDefinitionInterface) {
      return $fieldDefinition->getCardinality();
    }

    return NULL;
  }

}
