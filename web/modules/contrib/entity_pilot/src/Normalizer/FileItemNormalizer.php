<?php

namespace Drupal\entity_pilot\Normalizer;

use Drupal\hal\Normalizer\EntityReferenceItemNormalizer;

/**
 * File item normalizer.
 */
class FileItemNormalizer extends EntityReferenceItemNormalizer {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = 'Drupal\file\Plugin\Field\FieldType\FileItem';

  /**
   * {@inheritdoc}
   */
  public function supportsNormalization($data, $format = NULL) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function constructValue($data, $context) {
    $field_item = $context['target_instance'];
    $field_definition = $field_item->getFieldDefinition();
    $target_type = $field_definition->getSetting('target_type');
    $values = NULL;
    if ($entity = $this->entityResolver->resolve($this, $data, $target_type)) {
      // The exists plugin manager may nominate an existing entity to use here.
      if ($id = $entity->id()) {
        return ['target_id' => $id];
      }
      return ['entity' => $entity];
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($field_item, $format = NULL, array $context = []) {
    /* @var $field_item \Drupal\file\Plugin\Field\FieldType\FileItem */

    $data = parent::normalize($field_item, $format, $context);

    // Copied from parent implementation.
    $field_name = $field_item->getParent()->getName();
    $entity = $field_item->getEntity();
    $field_uri = $this->linkManager->getRelationUri($entity->getEntityTypeId(), $entity->bundle(), $field_name);

    // Set file field-specific values.
    $data['_embedded'][$field_uri][0]['display'] = $field_item->get('display')->getValue();
    $data['_embedded'][$field_uri][0]['description'] = $field_item->get('description')->getValue();
    return $data;
  }

}
