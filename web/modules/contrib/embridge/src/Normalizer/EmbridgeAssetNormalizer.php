<?php
/**
 * @file
 * Contains \Drupal\embridge\Normalizer\EmbridgeAssetNormalizer.
 */

namespace Drupal\embridge\Normalizer;

use Drupal\Core\Entity\EntityInterface;
use Drupal\hal\Normalizer\EntityReferenceItemNormalizer;

/**
 * Class EmbridgeAssetNormalizer.
 */
class EmbridgeAssetNormalizer extends EntityReferenceItemNormalizer {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = 'Drupal\embridge\Plugin\Field\FieldType\EmbridgeAssetItem';

  /**
   * {@inheritdoc}
   */
  public function normalize($field_item, $format = NULL, array $context = array()) {
    /* @var $field_item \Drupal\Core\Field\FieldItemInterface */
    $data = parent::normalize($field_item, $format, $context);

    // Copied from parent implementation.
    $field_name = $field_item->getParent()->getName();
    $entity = $field_item->getEntity();
    $field_uri = $this->linkManager->getRelationUri($entity->getEntityTypeId(), $entity->bundle(), $field_name);

    // Set embridge field-specific values.
    $data['_embedded'][$field_uri][0]['description'] = $field_item->get('description')->getValue();
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function constructValue($data, $context) {
    $value = NULL;

    /*
     * Copied from entity_pilot's normalizer.
     * @see \Drupal\embridge\EmbridgeServiceProvider::alter
     */
    $field_item = $context['target_instance'];
    $field_definition = $field_item->getFieldDefinition();
    $target_type = $field_definition->getSetting('target_type');
    if ($entity = $this->entityResolver->resolve($this, $data, $target_type)) {
      // If we've used entity pilot's unsaved uuid resolver it'll be an entity.
      if ($entity instanceof EntityInterface && $id = $entity->id()) {
        $value = ['target_id' => $id];
      }
      elseif (!$entity instanceof EntityInterface) {
        $value = ['target_id' => $entity];
      }
      else {
        // Unsaved uuid resolver will fix this up later.
        $value = ['entity' => $entity];
      }
    }

    // Add the description in if it exists.
    if (!empty($data['description'])) {
      $value['description'] = $data['description'];
    }

    return $value;
  }

}
