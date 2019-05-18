<?php

namespace Drupal\date_time_day\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\date_time_day\Plugin\Field\FieldType\DateTimeDayItem;

/**
 * Plugin implementation of the 'datetimeday_h_i_s_time' widget.
 *
 * @FieldWidget(
 *   id = "datetimeday_h_i_s_time",
 *   label = @Translation("Date time day with seconds"),
 *   field_types = {
 *     "datetimeday"
 *   }
 * )
 */
class DateTimeDaySecondsWidget extends DateTimeDayWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    // Identify the type of date and time elements to use.
    switch ($this->getFieldSetting('datetime_type')) {
      case DateTimeDayItem::DATEDAY_TIME_DEFAULT_TYPE_FORMAT:
      case DateTimeDayItem::DATEDAY_TIME_TYPE_SECONDS_FORMAT:
        // Date field properties.
        $value_date_format = DATETIME_DATE_STORAGE_FORMAT;
        $value_date_type = 'date';
        $value_time_format = '';
        $value_time_type = 'none';
        // Time fields properties.
        $time_date_format = '';
        $time_date_type = 'none';
        $time_format = DateTimeDayItem::DATE_TIME_DAY_H_I_S_FORMAT_STORAGE_FORMAT;
        $time_type = 'time';
        break;

      default:
        // Date field properties.
        $value_date_format = DATETIME_DATE_STORAGE_FORMAT;
        $value_date_type = 'date';
        $value_time_format = '';
        $value_time_type = 'none';
        // Time fields properties.
        $time_date_format = '';
        $value_date_type = 'none';
        $time_format = DateTimeDayItem::DATE_TIME_DAY_H_I_S_FORMAT_STORAGE_FORMAT;
        $time_type = 'time';
        break;
    }

    $element['value'] += [
      '#date_date_format' => $value_date_format,
      '#date_date_element' => $value_date_type,
      '#date_date_callbacks' => [],
      '#date_time_format' => $value_time_format,
      '#date_time_element' => $value_time_type,
      '#date_time_callbacks' => [],
    ];

    $element['start_time_value'] += [
      '#date_date_format' => $time_date_format,
      '#date_date_element' => $time_date_type,
      '#date_date_callbacks' => [],
      '#date_time_format' => $time_format,
      '#date_time_element' => $time_type,
      '#date_time_callbacks' => [],
    ];

    $element['end_time_value'] += [
      '#date_date_format' => $time_date_format,
      '#date_date_element' => $time_date_type,
      '#date_date_callbacks' => [],
      '#date_time_format' => $time_format,
      '#date_time_element' => $time_type,
      '#date_time_callbacks' => [],
    ];

    return $element;
  }

}
