<?php

namespace Drupal\usable_json\Normalizer;

use Drupal\link\Plugin\Field\FieldType\LinkItem;
use Drupal\serialization\Normalizer\ComplexDataNormalizer;

/**
 * Decorator for LinkItem to normalize to normal path.
 */
class LinkItemNormalizer extends ComplexDataNormalizer {

  use ImageItemNormalizerTrait;

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = LinkItem::class;

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
    /* @var \Drupal\link\Plugin\Field\FieldType\LinkItem $field_item */
    $normalization = parent::normalize($field_item, $format, $context);
    $normalization['uri'] = $field_item->getUrl()->toString();

    return $normalization;
  }

}
