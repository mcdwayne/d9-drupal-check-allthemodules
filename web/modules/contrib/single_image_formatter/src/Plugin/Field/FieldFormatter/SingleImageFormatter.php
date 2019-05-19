<?php

namespace Drupal\single_image_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;

/**
 * Plugin implementation of the 'single_image_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "single_image_formatter",
 *   label = @Translation("Single image formatter"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class SingleImageFormatter extends ImageFormatter {

  /**
   * {@inheritdoc}
   */
  protected function getEntitiesToView(EntityReferenceFieldItemListInterface $items, $langcode) {
    $files = parent::getEntitiesToView($items, $langcode);
    $file = reset($files);
    return $file ? [$file] : [];
  }

}
