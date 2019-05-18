<?php

namespace Drupal\field_slideshow\Plugin\FieldSlideshowPager;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\field_slideshow\FieldSlideshowPagerPluginBase;

/**
 * Plugin implementation of the field_slideshow_pager.
 *
 * @FieldSlideshowPager(
 *   id = "counter",
 *   label = @Translation("Counter"),
 *   description = @Translation("Counter description.")
 * )
 */
class Counter extends FieldSlideshowPagerPluginBase {

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
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#attributes' => [
          'class' => [
            'cycle-pager-item',
            'cycle-pager-item-' . ($delta + 1),
          ],
        ],
        '#value' => $delta + 1,
      ];
    }

    return $output;
  }

}
