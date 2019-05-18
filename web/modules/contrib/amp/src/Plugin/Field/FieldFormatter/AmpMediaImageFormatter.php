<?php

namespace Drupal\amp\Plugin\Field\FieldFormatter;

use Drupal\amp\Plugin\Field\FieldFormatter\AmpImageFormatter;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\media\Entity\MediaType;
use Drupal\media\Plugin\media\Source\Image;

/**
 * Plugin for amp media image formatter.
 *
 * @FieldFormatter(
 *   id = "amp_media",
 *   label = @Translation("Amp Media Image"),
 *   description = @Translation("Display media as an AMP Image file."),
 *   field_types = {
 *     "entity_reference",
 *   }
 * )
 *
 * @see \Drupal\amp\Plugin\Field\FieldFormatter\AmpImageFormatter
 * @see \Drupal\media\Plugin\Field\FieldFormatter\MediaThumbnailFormatter
 */
class AmpMediaImageFormatter extends AmpImageFormatter {

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

    $settings = $field_definition->getSettings();

    // This is an entity_reference that does not target media, this formatter
    // does not apply.
    if ($settings['target_type'] != 'media') {
      return FALSE;
    }

    // This field targets all media bundles, which might include images, this
    // formatter applies.
    if (empty($settings['handler_settings']) || empty($settings['handler_settings']['target_bundles'])) {
      return TRUE;
    }
    else {
      foreach ($settings['handler_settings']['target_bundles'] as $bundle) {
        $media_type = MediaType::load($bundle);

        // This field specifically targets bundles of the Image media type, this
        // formatter applies
        if ($media_type && $media_type->getSource() instanceof Image) {
          return TRUE;
        }
      }
    }

    // None of the above is true, this field targets non-image media, this
    // formatter does not apply.
    return FALSE;
  }

}
