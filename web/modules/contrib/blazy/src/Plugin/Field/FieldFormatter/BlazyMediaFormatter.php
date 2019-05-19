<?php

namespace Drupal\blazy\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Plugin for blazy media formatter.
 *
 * @FieldFormatter(
 *   id = "blazy_media",
 *   label = @Translation("Blazy"),
 *   field_types = {
 *     "entity_reference",
 *   }
 * )
 *
 * @see \Drupal\blazy\Plugin\Field\FieldFormatter\BlazyFormatter
 * @see \Drupal\media\Plugin\Field\FieldFormatter\MediaThumbnailFormatter
 */
class BlazyMediaFormatter extends BlazyFormatter {

  /**
   * {@inheritdoc}
   */
  protected function getEntitiesToView(EntityReferenceFieldItemListInterface $items, $langcode) {
    $media = parent::getEntitiesToView($items, $langcode);
    $entities = [];
    foreach ($media as $media_item) {
      $entity = $media_item->thumbnail->entity;
      $entity->_referringItem = $media_item->thumbnail;
      $entities[] = $entity;
    }
    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return $field_definition->getFieldStorageDefinition()->getSetting('target_type') == 'media';
  }

}
