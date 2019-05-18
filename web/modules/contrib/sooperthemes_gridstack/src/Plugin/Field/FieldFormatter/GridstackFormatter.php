<?php

namespace Drupal\sooperthemes_gridstack\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;

/**
 * Plugin implementation of the 'Gridstack' formatter.
 *
 * @FieldFormatter(
 *   id = "sooperthemes_gridstack_gridstack",
 *   label = @Translation("Gridstack"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class GridstackFormatter extends ImageFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);
    foreach ($elements as $delta => $element) {
      $elements[$delta]['#theme'] = 'sooperthemes_gridstack_gridstack_formatter';
    }
    return $elements;
  }

}
