<?php

namespace Drupal\smallads\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\BooleanFormatter;

/**
 * Field formatter.
 *
 * Shows an empty div with class according to whether the given date is before.
 * or after now.
 *
 * @FieldFormatter(
 *   id = "time_before_after",
 *   label = @Translation("Before or after the time"),
 *   field_types = {
 *     "datetime",
 *   }
 * )
 */
class TimeBeforeAfterFormatter extends BooleanFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    // @todo include the library
    $elements = array();
    foreach ($items as $delta => &$item) {
      if (REQUEST_TIME > $item->value) {
        $item->value = FALSE;
        $class = 'expired';
      }
      else {
        $item->value = TRUE;
        $class = 'extant';
      }
      $elements = [];
      foreach (parent::viewElements($items, $langcode) as $delta => &$item) {
        // $item['#prefix'] = '<div class = "'. $class .'">';
        // $item['#suffix'] = '</div>';.
        $elements[$delta]['#markup'] = "<div class = \"$class\">" . $item['#markup'] . '</div>';
      }
    }
    // Allows intervention from themers..
    $elements['#attached'] = ['library' => ['smallads/css']];
    return $elements;
  }

}
