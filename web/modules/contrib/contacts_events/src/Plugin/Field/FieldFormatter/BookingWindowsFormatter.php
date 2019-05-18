<?php

namespace Drupal\contacts_events\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\datetime\Plugin\Field\FieldFormatter\DateTimeDefaultFormatter;

/**
 * Plugin implementation of the 'booking_windows' formatter.
 *
 * @FieldFormatter(
 *   id = "booking_windows",
 *   label = @Translation("Booking windows"),
 *   field_types = {
 *     "booking_windows"
 *   }
 * )
 */
class BookingWindowsFormatter extends DateTimeDefaultFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = ['#markup' => $this->viewValue($item)];
    }

    return $elements;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return string
   *   The textual output generated.
   */
  protected function viewValue(FieldItemInterface $item) {
    $date = $item->date;
    if ($date) {
      $this->setTimeZone($date);
      return $this->t('@label: Ends @cutoff', [
        '@label' => $item->label,
        '@cutoff' => $this->formatDate($date),
      ]);
    }
    else {
      return Html::escape($item->label);
    }
  }

}
