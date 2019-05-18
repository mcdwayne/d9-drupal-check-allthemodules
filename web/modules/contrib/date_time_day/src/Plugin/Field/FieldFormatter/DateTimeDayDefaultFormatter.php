<?php

namespace Drupal\date_time_day\Plugin\Field\FieldFormatter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\Field\FieldFormatter\DateTimeDefaultFormatter;
use Drupal\date_time_day\DateTimeDayTrait;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;

/**
 * Plugin implementation of the 'Default' formatter for 'datetimeday' fields.
 *
 * This formatter renders the data time day using <time> elements, with
 * configurable date formats (from the list of configured formats) and
 * separators.
 *
 * @FieldFormatter(
 *   id = "datetimeday_default",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "datetimeday"
 *   }
 * )
 */
class DateTimeDayDefaultFormatter extends DateTimeDefaultFormatter {

  use DateTimeDayTrait;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $default_settings = [
      'day_separator' => ',',
      'time_separator' => '-',
      'time_format_type' => 'html_time',
    ] + parent::defaultSettings();
    // Override format type with our custom value.
    $default_settings['format_type'] = 'html_date';
    return $default_settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['format_type']['#title'] = $this->t('Day format');
    $form['format_type']['#description'] = $this->t("Choose a format for displaying the day. Be sure to set a format appropriate for the field, i.e. omitting time for a field that only has a date.");

    $form['time_format_type'] = [
      '#type' => 'select',
      '#title' => t('Time format'),
      '#description' => t("Choose a format for displaying the time. Be sure to set a format appropriate for the field, i.e. omitting date for a field that only has a time."),
      '#options' => $form['format_type']['#options'],
      '#default_value' => $this->getSetting('time_format_type'),
    ];

    $form['day_separator'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Day separator'),
      '#description' => $this->t('The string to separate the day and start, end times'),
      '#default_value' => $this->getSetting('day_separator'),
    ];

    $form['time_separator'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Time separator'),
      '#description' => $this->t('The string to separate start, end times'),
      '#default_value' => $this->getSetting('time_separator'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    if ($override = $this->getSetting('timezone_override')) {
      $summary[] = $this->t('Time zone: @timezone', ['@timezone' => $override]);
    }

    $date = new DrupalDateTime();
    $summary[] = t('Day format: @display', ['@display' => $this->formatDate($date)]);
    $summary[] = t('Time format: @display', ['@display' => $this->formatTime($date)]);
    if ($day_separator = $this->getSetting('day_separator')) {
      $summary[] = $this->t('Day separator: %day_separator', ['%day_separator' => $day_separator]);
    }

    if ($time_separator = $this->getSetting('time_separator')) {
      $summary[] = $this->t('Time separator: %time_separator', ['%time_separator' => $time_separator]);
    }

    return $summary;
  }

  /**
   * Formats the date to time format.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $date
   *   The date to format to time.
   *
   * @return string
   *   The formatted time string.
   */
  protected function formatTime(DrupalDateTime $date) {
    $format_type = $this->getSetting('time_format_type');
    $timezone = $this->getSetting('timezone_override') ?: $date->getTimezone()->getName();
    return $this->dateFormatter->format($date->getTimestamp(), $format_type, '', $timezone != '' ? $timezone : NULL);
  }

  /**
   * Creates a render array from a date object with time attribute.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $dateTime
   *   A date object.
   *
   * @return array
   *   A render array.
   */
  protected function buildTimeWithAttribute(DrupalDateTime $dateTime) {
    $build = [
      '#theme' => 'time',
      '#text' => $this->formatTime($dateTime),
      '#html' => FALSE,
      '#attributes' => [
        'time' => $this->formatTime($dateTime),
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
   *
   * @todo: this fix only for Drupal 8.3.7 and earlier, think about remove.
   * @see https://www.drupal.org/project/projectapplications/issues/2958753
   */
  protected function buildDateWithIsoAttribute(DrupalDateTime $date) {
    if ($this->getFieldSetting('datetime_type') == DateTimeItem::DATETIME_TYPE_DATE) {
      // A date without time will pick up the current time, use the default.
      datetime_date_default_time($date);
    }

    // Create the ISO date in Universal Time.
    $iso_date = $date->format("Y-m-d\TH:i:s") . 'Z';

    $this->setTimeZone($date);

    $build = [
      '#theme' => 'time',
      '#text' => $this->formatDate($date),
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

}
