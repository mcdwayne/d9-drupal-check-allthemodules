<?php

namespace Drupal\entity_normalization_normalizers\Normalizer;

use Drupal\text\Plugin\Field\FieldType\TextLongItem;
use Drupal\text\Plugin\Field\FieldType\TextWithSummaryItem;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Normalizer for rich text fields.
 */
class RichTextNormalizer implements NormalizerInterface {

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []) {
    /** @var \Drupal\text\Plugin\Field\FieldType\TextItemBase $object */
    return $object->getValue()['value'];
  }

  /**
   * {@inheritdoc}
   */
  public function supportsNormalization($data, $format = NULL) {
    return $data instanceof TextLongItem || $data instanceof TextWithSummaryItem;
  }

}
