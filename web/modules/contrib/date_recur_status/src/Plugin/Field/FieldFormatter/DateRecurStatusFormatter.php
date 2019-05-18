<?php

namespace Drupal\date_recur_status\Plugin\Field\FieldFormatter;

use Drupal\date_recur\Plugin\Field\FieldFormatter\DateRecurDefaultFormatter;
use Drupal\date_recur\Plugin\Field\FieldType\DateRecurItem;

/**
 * Plugin implementation of the 'date_recur_status_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "date_recur_status_formatter",
 *   label = @Translation("Date recur status formatter"),
 *   field_types = {
 *     "date_recur"
 *   }
 * )
 */
class DateRecurStatusFormatter extends DateRecurDefaultFormatter {

  protected function viewValue(DateRecurItem $item) {
    $build = parent::viewValue($item);
    $build['#theme'] = 'date_recur_status_formatter';
    return $build;
  }

  protected function viewOccurrences(DateRecurItem $item) {
    $build = [];
    $start = new \DateTime('now');

    $count = $this->getSetting('show_next');
    if (!$this->getSetting('count_per_item')) {
      $count = $count - $this->occurrenceCounter;
    }
    if ($count <= 0) {
      return $build;
    }

    $occurrences = $item->getOccurrenceHandler()->getOccurrencesForDisplay($start, NULL, $count);
    foreach ($occurrences as $occurrence) {
      if (!empty($occurrence['value'])) {
        $row['date'] = $this->buildDateRangeValue($occurrence['value'], $occurrence['end_value'], TRUE);
        if (!empty($occurrence['status'])) {
          $row['status'] = $occurrence['status'];
        }
        $build[] = $row;
      }
    }
    $this->occurrenceCounter += count($occurrences);
    return $build;
  }
}
