<?php

namespace Drupal\usable_json\Normalizer;

use Drupal\serialization\Normalizer\ComplexDataNormalizer;
use Drupal\webform\Plugin\Field\FieldType\WebformEntityReferenceItem;

/**
 * Defines a class for normalizing WebformEntityReferenceItems.
 */
class WebformEntityReferenceItemNormalizer extends ComplexDataNormalizer {

  /**
   * The formats that the Normalizer can handle.
   *
   * @var array
   */
  protected $format = ['usable_json'];

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = WebformEntityReferenceItem::class;

  /**
   * {@inheritdoc}
   */
  public function normalize($field_item, $format = NULL, array $context = []) {
    $entity = $field_item->get('entity')->getValue();
    $values = $this->serializer->normalize($entity, 'usable_json', $context);

    return $values;
  }

}
