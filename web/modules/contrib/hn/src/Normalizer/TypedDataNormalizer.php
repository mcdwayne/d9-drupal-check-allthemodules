<?php

namespace Drupal\hn\Normalizer;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\serialization\Normalizer\TypedDataNormalizer as SerializationTypedDataNormalizer;

/**
 * Normalizes TypedData.
 */
class TypedDataNormalizer extends SerializationTypedDataNormalizer {

  protected $format = ['hn'];

  /**
   * The normalizer used to normalize the typed data.
   *
   * @var \Symfony\Component\Serializer\Normalizer\NormalizerInterface
   */
  protected $serializer;

  protected $serializingParent = FALSE;

  /**
   * {@inheritdoc}
   */
  public function supportsNormalization($data, $format = NULL) {
    if ($this->serializingParent) {
      $this->serializingParent = FALSE;
      // Let parent handle it.
      return FALSE;
    }
    return parent::supportsNormalization($data, $format);
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []) {

    /* @var $object \Drupal\Core\TypedData\TypedDataInterface */
    if (!$this->serializer) {
      $this->serializer = \Drupal::service('serializer');
    }

    $this->serializingParent = TRUE;
    $value = $this->serializer->normalize($object, $format, $context);
    $this->serializingParent = FALSE;

    // If this is a field with never more then 1 value, show the first value.
    if ($object instanceof FieldItemListInterface) {
      $cardinality = $object->getFieldDefinition()->getFieldStorageDefinition()->getCardinality();
      if ($cardinality === 1) {
        if (isset($value[0])) {
          $value = $value[0];
        }
        else {
          $value = NULL;
        }
      }
    }

    // If the value is an associative array with 'value' as only key,
    // return the value of 'value'.
    if (is_array($value) && isset($value['value']) && count($value) === 1) {
      $value = $value['value'];
    }

    return $value;
  }

}
