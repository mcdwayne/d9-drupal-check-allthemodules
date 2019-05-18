<?php

namespace Drupal\image_with_title\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;

/**
 * Plugin implementation of the 'image_with_title' formatter.
 *
 * @FieldFormatter(
 *   id = "image_with_title",
 *   label = @Translation("Image with title"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class ImageWithTitle extends ImageFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);
    foreach ($elements as $key => $element) {
      $elements[$key]['#theme'] = 'image_with_title_formatter';
    }
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Column to display: Image with title');
    return $summary;
  }

}
