<?php

namespace Drupal\flexible_daterange\Plugin\Field\FieldFormatter;

use DateTime;
use DateTimeZone;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\flexible_daterange\Plugin\Field\FieldType\FlexibleDateRangeItem;


/**
 * Plugin implementation of the 'Flexible daterange' interval field formatter.
 *
 * @FieldFormatter(
 *   id = "flexible_daterange_interval",
 *   label = @Translation("Interval"),
 *   field_types = {
 *     "flexible_daterange"
 *   }
 * )
 */
class FlexibleDateRangeIntervalFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    /**
     * @var integer $delta
     * @var FlexibleDateRangeItem $item
     */
    foreach ($items as $delta => $item) {
      /** @var \Drupal\Core\Field\FieldItemListInterface $item */
      $start = $item->getValue()['value'];
      $end = $item->getValue()['end_value'];
      $field_type = @$item->getFieldDefinition()->getSettings()['datetime_type'];
      if ($field_type == 'datetime') {
        $start_datetime = DateTime::createFromFormat('Y-m-d\TH:i:s', $start, new DateTimeZone('UTC'));
        $end_datetime = DateTime::createFromFormat('Y-m-d\TH:i:s', $end, new DateTimeZone('UTC'));
      }
      else {
        // # 'date' or 'allday'
        $start_datetime = DateTime::createFromFormat('Y-m-d', $start, new DateTimeZone('UTC'));
        $end_datetime = DateTime::createFromFormat('Y-m-d', $end, new DateTimeZone('UTC'));
      }

      if ($start_datetime && $end_datetime) {
        $start_datetime->setTimezone(new \DateTimeZone(drupal_get_user_timezone()));
        $end_datetime->setTimezone(new \DateTimeZone(drupal_get_user_timezone()));

        $same_year = $start_datetime->format('Y') == $end_datetime->format('Y');
        $same_month = $start_datetime->format('m') == $end_datetime->format('m') && $same_year;
        $same_day = $start_datetime->format('d') == $end_datetime->format('d') && $same_month;

        $markup = '';
        if ($same_day) {
          $markup .= $start_datetime->format('d F Y');
        }
        elseif ($same_month) {
          $markup .= $this->t('@startday - @endday', [
            '@startday' => $start_datetime->format('d'),
            '@endday' => $end_datetime->format('d'),
          ]);
          $markup .= ' ' . $start_datetime->format('F Y');
        }

        elseif ($same_year) {
          $markup .= $this->t('@startday - @endday', [
            '@startday' => $start_datetime->format('d F'),
            '@endday' => $end_datetime->format('d F'),
          ]);
          $markup .= ' ' . $start_datetime->format('Y');
        }
        else {
          $markup .= $this->t('@startday - @endday', [
            '@startday' => $start_datetime->format('d F Y'),
            '@endday' => $end_datetime->format('d F Y'),
          ]);
        }

        if (empty($item->hide_time)) {
          $markup .= ' ' . $this->t('(@starttime - @endtime)', [
              '@starttime' => $start_datetime->format('H:i'),
              '@endtime' => $end_datetime->format('H:i'),
            ]);
        }
      }
      else {
        $markup = $this->t("Date ERR (start={$start}, end={$end})");
      }

      $element[$delta] = [
        '#type' => 'markup',
        '#markup' => $markup,
      ];
    }
    return $element;
  }

}
