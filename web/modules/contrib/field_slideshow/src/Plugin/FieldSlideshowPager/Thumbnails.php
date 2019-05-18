<?php

namespace Drupal\field_slideshow\Plugin\FieldSlideshowPager;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\field_slideshow\FieldSlideshowPagerPluginBase;

/**
 * Plugin implementation of the field_slideshow_pager.
 *
 * @FieldSlideshowPager(
 *   id = "thumbnails",
 *   label = @Translation("Thumbnails"),
 *   description = @Translation("Thumbnails description.")
 * )
 */
class Thumbnails extends FieldSlideshowPagerPluginBase {

  /**
   * Function render pager.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   List of items.
   *
   * @return array
   *   Rendered array.
   */
  public function viewPager(FieldItemListInterface $items) {
    $output = [];

    foreach ($items as $delta => $item) {

      $output[$delta] = [
        '#theme' => 'image_style',
        '#height' => '',
        '#width' => '',
        '#style_name' => 'thumbnail',
        '#uri' => $item->entity->getFileUri(),
      ];
    }

    return $output;
  }

}
