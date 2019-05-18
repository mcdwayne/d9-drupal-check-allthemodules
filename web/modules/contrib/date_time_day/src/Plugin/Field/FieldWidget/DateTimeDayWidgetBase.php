<?php

namespace Drupal\date_time_day\Plugin\Field\FieldWidget;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\Field\FieldWidget\DateTimeWidgetBase;
use Drupal\date_time_day\Plugin\Field\FieldType\DateTimeDayItem;

/**
 * Base class for the 'datetimeday_*' widgets.
 */
class DateTimeDayWidgetBase extends DateTimeWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    // Wrap all of the selected elements with a fieldset.
    $element['#theme_wrappers'][] = 'fieldset';

    $element['value']['#title'] = $this->t('Date');

    $element['start_time_value'] = [
      '#title' => $this->t('Start time'),
    ] + $element['value'];

    $element['end_time_value'] = [
      '#title' => $this->t('End time'),
    ] + $element['value'];
    if ($items[$delta]->date) {
      /** @var \Drupal\Core\Datetime\DrupalDateTime $value */
      $value = $items[$delta]->date;
      $element['value']['#default_value'] = $this->createDateTimeDayDefaultValue($value, $element['value']['#date_timezone']);
    }

    if ($items[$delta]->start_time) {
      /** @var \Drupal\Core\Datetime\DrupalDateTime $start_date */
      $start_time = $items[$delta]->start_time;
      $element['start_time_value']['#default_value'] = $this->createDateTimeDayDefaultValue($start_time, $element['start_time_value']['#date_timezone']);
    }

    if ($items[$delta]->end_time) {
      /** @var \Drupal\Core\Datetime\DrupalDateTime $end_date */
      $end_time = $items[$delta]->end_time;
      $element['end_time_value']['#default_value'] = $this->createDateTimeDayDefaultValue($end_time, $element['end_time_value']['#date_timezone']);
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    // The widget form element type has transformed the value to a
    // DrupalDateTime object at this point. We need to convert it back to the
    // storage timezone and format.
    foreach ($values as &$item) {
      if (!empty($item['value']) && $item['value'] instanceof DrupalDateTime) {
        /** @var \Drupal\Core\Datetime\DrupalDateTime $value_date */
        $value_date = $item['value'];
        $value_format = DATETIME_DATE_STORAGE_FORMAT;
        // Adjust the date for storage.
        $value_date->setTimezone(new \DateTimezone(DATETIME_STORAGE_TIMEZONE));
        $item['value'] = $value_date->format($value_format);
      }
      if (!empty($item['start_time_value']) && $item['start_time_value'] instanceof DrupalDateTime) {
        /** @var \Drupal\Core\Datetime\DrupalDateTime $start_time_date */
        $start_time_date = $item['start_time_value'];
        $start_time_format = '';
        switch ($this->getFieldSetting('datetime_type')) {
          case DateTimeDayItem::DATEDAY_TIME_DEFAULT_TYPE_FORMAT:
            $start_time_format = DateTimeDayItem::DATE_TIME_DAY_H_I_FORMAT_STORAGE_FORMAT;
            break;

          case DateTimeDayItem::DATEDAY_TIME_TYPE_SECONDS_FORMAT:
            $start_time_format = DateTimeDayItem::DATE_TIME_DAY_H_I_S_FORMAT_STORAGE_FORMAT;
            break;

          default:
            $start_time_format = DateTimeDayItem::DATE_TIME_DAY_H_I_S_FORMAT_STORAGE_FORMAT;
            break;
        }
        // Adjust the date for storage.
        $start_time_date->setTimezone(new \DateTimezone(DATETIME_STORAGE_TIMEZONE));
        $item['start_time_value'] = $start_time_date->format($start_time_format);
      }

      if (!empty($item['end_time_value']) && $item['end_time_value'] instanceof DrupalDateTime) {
        /** @var \Drupal\Core\Datetime\DrupalDateTime $end_time_date */
        $end_time_date = $item['end_time_value'];
        $end_time_format = '';
        switch ($this->getFieldSetting('datetime_type')) {
          case DateTimeDayItem::DATEDAY_TIME_DEFAULT_TYPE_FORMAT:
            $end_time_format = DateTimeDayItem::DATE_TIME_DAY_H_I_FORMAT_STORAGE_FORMAT;
            break;

          case DateTimeDayItem::DATEDAY_TIME_TYPE_SECONDS_FORMAT:
            $end_time_format = DateTimeDayItem::DATE_TIME_DAY_H_I_S_FORMAT_STORAGE_FORMAT;
            break;

          default:
            $end_time_format = DateTimeDayItem::DATE_TIME_DAY_H_I_S_FORMAT_STORAGE_FORMAT;
            break;
        }
        // Adjust the date for storage.
        $end_time_date->setTimezone(new \DateTimezone(DATETIME_STORAGE_TIMEZONE));
        $item['end_time_value'] = $end_time_date->format($end_time_format);
      }
    }
    return $values;
  }

  /**
   * Creates a date object for use as a default value.
   *
   * This will take a default value, apply the proper timezone for display in
   * a widget, and set the default time for date-only fields.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $date
   *   The UTC default date.
   * @param string $timezone
   *   The timezone to apply.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   *   A date object for use as a default value in a field widget.
   */
  protected function createDateTimeDayDefaultValue(DrupalDateTime $date, $timezone) {
    $date->setTimezone(new \DateTimeZone($timezone));
    return $date;
  }

}
