<?php

namespace Drupal\single_image_formatter_media\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\media\Plugin\Field\FieldFormatter\MediaThumbnailFormatter;

/**
 * Plugin implementation of the 'single_media_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "single_media_formatter",
 *   label = @Translation("Single media thumbnail"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class SingleMediaFormatter extends MediaThumbnailFormatter {

  /**
   * {@inheritdoc}
   */
  protected function getEntitiesToView(EntityReferenceFieldItemListInterface $items, $langcode) {
    $files = parent::getEntitiesToView($items, $langcode);
    $file = reset($files);
    return $file ? [$file] : [];
  }
}
