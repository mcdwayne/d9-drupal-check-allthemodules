<?php

namespace Drupal\date_all_day;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * A viewElements method, that respects an empty end date.
 * @todo Check $item->value_all_day and end_value_all_day to hide the time and add (All day) label.
 */
trait DateRangeAllDayTrait {

  /**
   * {@inheritdoc}
   *
   * @see Drupal\date_all_day\DateRangeAllDayTrait
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $separator = $this->getSetting('separator');

    foreach ($items as $delta => $item) {
      if (!empty($item->start_date)) {
        /** @var \Drupal\Core\Datetime\DrupalDateTime $start_date */
        $start_date = $item->start_date;
        /** @var \Drupal\Core\Datetime\DrupalDateTime $end_date */
        $end_date = $item->end_date;

        $item_value = $item->getValue();

        if ($end_date !== NULL && $start_date->getTimestamp() !== $end_date->getTimestamp()) {
          $elements[$delta] = [
            'start_date' => $this->buildDateWithIsoAttribute($start_date, (bool) $item_value['value_all_day']),
            'separator' => ['#plain_text' => ' ' . $separator . ' '],
            'end_date' => $this->buildDateWithIsoAttribute($end_date, (bool) $item_value['end_value_all_day']),
          ];
        }
        else {
          $elements[$delta] = $this->buildDateWithIsoAttribute($start_date, (bool) $item_value['value_all_day']);

          if (!empty($item->_attributes)) {
            $elements[$delta]['#attributes'] += $item->_attributes;
            // Unset field item attributes since they have been included in the
            // formatter output and should not be rendered in the field template.
            unset($item->_attributes);
          }
        }
      }
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildDate(DrupalDateTime $date, $all_day = FALSE) {
    $this->setTimeZone($date);

    $build = [
      '#markup' => $this->formatDate($date, $all_day),
      '#cache' => [
        'contexts' => [
          'timezone',
        ],
      ],
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildDateWithIsoAttribute(DrupalDateTime $date, $all_day = FALSE) {
    // Create the ISO date in Universal Time.
    $iso_date = $date->format("Y-m-d\TH:i:s") . 'Z';

    $this->setTimeZone($date);

    $build = [
      '#theme' => 'time',
      '#text' => $this->formatDate($date, $all_day) . ($all_day? ' ' . $this->t('(All day)') : '' ),
      '#html' => FALSE,
      '#attributes' => [
        'datetime' => $iso_date,
      ],
      '#cache' => [
        'contexts' => [
          'timezone',
        ],
      ],
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    if ($date_only_format = $this->getSetting('date_only_format')) {
      $date = new DrupalDateTime();
      $this->setTimeZone($date);
      $formatted_date = $this->dateFormatter->format($date->getTimestamp(), $date_only_format);

      $summary[] = $this->t('Date only format: %date', ['%date' => $formatted_date]);
    }

    return $summary;
  }
}
