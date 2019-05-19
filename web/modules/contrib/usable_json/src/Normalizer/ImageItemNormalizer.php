<?php

namespace Drupal\usable_json\Normalizer;

use Drupal\image\Plugin\Field\FieldType\ImageItem;
use Drupal\serialization\Normalizer\ComplexDataNormalizer;

/**
 * Decorator for ImageItem HAL normalizer providing URLs to image styles.
 */
class ImageItemNormalizer extends ComplexDataNormalizer {

  use ImageItemNormalizerTrait;

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = ImageItem::class;

  /**
   * The formats that the Normalizer can handle.
   *
   * @var array
   */
  protected $format = ['usable_json'];

  /**
   * {@inheritdoc}
   */
  public function normalize($field_item, $format = NULL, array $context = []) {
    /* @var \Drupal\image\Plugin\Field\FieldType\ImageItem $field_item */
    $normalization = parent::normalize($field_item, $format, $context);
    if (!$field_item->isEmpty()) {
      $this->decorateWithImageStyles($field_item, $normalization, $context);
    }
    return $normalization;
  }

}
