<?php

namespace Drupal\staged_content\Normalizer;

use Drupal\serialization\Normalizer\FieldItemNormalizer;

/**
 * Adds the file URI to embedded file entities.
 */
class EntityReferenceViewsFieldItemNormalizer extends FieldItemNormalizer {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = 'Drupal\viewsreference\Plugin\Field\FieldType\ViewsReferenceItem';

  /**
   * {@inheritdoc}
   */
  protected $format = ['storage_json'];

  /**
   * {@inheritdoc}
   */
  public function normalize($field_item, $format = NULL, array $context = []) {
    $values = parent::normalize($field_item, $format, $context);
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  protected function constructValue($data, $context) {
    return $data;
  }

}
