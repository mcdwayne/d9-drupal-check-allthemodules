<?php

namespace Drupal\picture_background_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Plugin implementation of the 'picture_background_formatter_media' formatter.
 *
 * @FieldFormatter(
 *   id = "picture_background_formatter_media",
 *   label = @Translation("Picture background formatter"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class PictureBackgroundFormatterMedia extends PictureBackgroundFormatter {

  /**
   * {@inheritdoc}
   *
   * This has to be overriden because FileFormatterBase expects $item to be
   * of type \Drupal\file\Plugin\Field\FieldType\FileItem and calls
   * isDisplayed() which is not in FieldItemInterface.
   */
  protected function needsEntityLoad(EntityReferenceItem $item) {
    return !$item->hasNewEntity();
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {;
    $media = $this->getEntitiesToView($items, $langcode);
    $files = [];

    // Early opt-out if the field is empty.
    if (empty($media)) {
      return [];
    }

    // The parent entity, used for token replacement in the selector.
    $entity = $items->getEntity();

    /** @var \Drupal\media\MediaInterface $media_item */
    foreach ($media as $delta => $media_item) {
      $type_configuration = method_exists($media_item, 'getSource') ? $media_item->getSource()->getConfiguration() : $media_item->getType()->getConfiguration();

      // Get the actual image entities from the media item.
      $image_items = isset($type_configuration['source_field'])
        ? $media_item->get($type_configuration['source_field'])
        : NULL;

      foreach ($image_items as $image_item) {
        // Get each file entity.
        $files[] = $image_item->entity;
      }
    }

    return $this->build_element($files, $entity);
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    // This formatter is only available for entity types that reference
    // media entities.
    $target_type = $field_definition->getFieldStorageDefinition()->getSetting('target_type');
    return $target_type == 'media';
  }

}
