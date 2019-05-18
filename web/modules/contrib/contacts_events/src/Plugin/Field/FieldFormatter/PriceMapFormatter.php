<?php

namespace Drupal\contacts_events\Plugin\Field\FieldFormatter;

use Drupal\commerce_price\Plugin\Field\FieldFormatter\PriceDefaultFormatter;
use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Language\LanguageInterface;

/**
 * Plugin implementation of the 'price_map' formatter.
 *
 * @FieldFormatter(
 *   id = "price_map",
 *   label = @Translation("Price map"),
 *   field_types = {
 *     "price_map"
 *   }
 * )
 */
class PriceMapFormatter extends PriceDefaultFormatter {

  /**
   * {@inheritdoc}
   */
  public function view(FieldItemListInterface $items, $langcode = NULL) {
    /* @var \Drupal\contacts_events\Plugin\Field\FieldType\PriceMapItemList $items */

    // Get the booking windows, ticket classes and the price map.
    $booking_windows = $items->getBookingWindows();
    $classes = $items->getClasses();
    $price_map = $items->getPriceMap();

    // If we are missing any data, return early.
    if (!$booking_windows || $booking_windows->count() == 0 || empty($classes) || empty($price_map)) {
      return [];
    }

    // Gather the currencies.
    $currency_codes = [];
    foreach ($items as $delta => $item) {
      $currency_codes[] = $item->currency_code;
    }
    $currencies = $currency_codes ? $this->currencyStorage->loadMultiple($currency_codes) : [];

    // Build our table.
    $elements = [
      '#type' => 'table',
      '#header' => ['_booking_window' => ''],
      '#rows' => [],
    ];

    foreach ($booking_windows as $booking_window) {
      $elements['#header'][$booking_window->id] = $booking_window->label;
    }

    foreach ($classes as $class) {
      $class_id = $class->id();
      $elements['#rows'][$class_id]['_booking_window'] = $class->label();

      foreach ($booking_windows as $booking_window) {
        if (!isset($price_map[$booking_window->id][$class_id])) {
          $elements['#rows'][$class_id][$booking_window->id] = '';
          continue;
        }

        $item = $price_map[$booking_window->id][$class_id];
        $currency = $currencies[$item->currency_code];
        $elements['#rows'][$class_id][$booking_window->id]['data'] = [
          '#markup' => $this->numberFormatter->formatCurrency($item->number, $currency),
          '#cache' => [
            'contexts' => [
              'languages:' . LanguageInterface::TYPE_INTERFACE,
              'country',
            ],
          ],
        ];
      }
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
    // The text value has no text format assigned to it, so the user input
    // should equal the output, including newlines.
    return nl2br(Html::escape($item->value));
  }

}
