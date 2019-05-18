<?php

namespace Drupal\date_time_day;

use Drupal\Core\Field\FieldItemListInterface;

/**
 * Provides friendly methods for date_time_day.
 */
trait DateTimeDayTrait {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $day_separator = $this->getSetting('day_separator');
    $time_separator = $this->getSetting('time_separator');

    foreach ($items as $delta => $item) {
      if (!empty($item->start_time) && !empty($item->end_time)) {
        $elements[$delta] = [
          'date' => $this->buildDateWithIsoAttribute($item->date),
          'day_separator' => ['#plain_text' => ' ' . $day_separator . ' '],
          'start_time' => $this->buildTimeWithAttribute($item->start_time),
          'time_separator' => ['#plain_text' => ' ' . $time_separator . ' '],
          'end_time' => $this->buildTimeWithAttribute($item->end_time),
        ];
      }
    }

    return $elements;
  }

}
