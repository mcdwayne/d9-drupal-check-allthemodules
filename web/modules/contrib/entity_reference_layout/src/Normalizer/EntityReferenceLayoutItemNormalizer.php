<?php

namespace Drupal\entity_reference_layout\Normalizer;

use Drupal\entity_reference_layout\Plugin\Field\FieldType\EntityReferenceLayoutRevisioned;
use Drupal\hal\Normalizer\EntityReferenceItemNormalizer;

/**
 * Defines a class for normalizing EntityReferenceLayoutItems.
 */
class EntityReferenceLayoutItemNormalizer extends EntityReferenceItemNormalizer {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = EntityReferenceLayoutRevisioned::class;

  /**
   * {@inheritdoc}
   */
  protected function constructValue($data, $context) {
    $value = parent::constructValue($data, $context);
    if ($value) {
      $value['target_revision_id'] = $data['target_revision_id'];
    }
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($field_item, $format = NULL, array $context = []) {
    /** @var \Drupal\Core\Field\FieldItemInterface $field_item */
    $data = parent::normalize($field_item, $format, $context);
    $field_name = $field_item->getParent()->getName();
    $entity = $field_item->getEntity();
    $field_uri = $this->linkManager->getRelationUri($entity->getEntityTypeId(), $entity->bundle(), $field_name, $context);
    $data['_embedded'][$field_uri][0]['target_revision_id'] = $field_item->target_revision_id;
    $data['_embedded'][$field_uri][0]['region'] = $field_item->region;
    $data['_embedded'][$field_uri][0]['layout'] = $field_item->layout;
    $data['_embedded'][$field_uri][0]['section_id'] = $field_item->section_id;
    $data['_embedded'][$field_uri][0]['options'] = $field_item->options;
    $data['_embedded'][$field_uri][0]['config'] = $field_item->config;
    return $data;
  }

}
