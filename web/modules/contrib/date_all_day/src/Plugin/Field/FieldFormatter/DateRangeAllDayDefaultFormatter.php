<?php

namespace Drupal\date_all_day\Plugin\Field\FieldFormatter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\date_all_day\DateRangeAllDayTrait;
use Drupal\datetime_range\Plugin\Field\FieldFormatter\DateRangeDefaultFormatter;

/**
 * Plugin implementation of the 'Default' formatter for 'daterange' fields.
 *
 * This formatter renders the data range using <time> elements, with
 * configurable date formats (from the list of configured formats) and a
 * separator.
 *
 * @FieldFormatter(
 *   id = "daterange_all_day_default",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "daterange_all_day"
 *   }
 * )
 */
class DateRangeAllDayDefaultFormatter extends DateRangeDefaultFormatter {

  use DateRangeAllDayTrait;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'date_only_format' => 'date_all_day',
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $options = $form['format_type']['#options'];

    $form['date_only_format'] = [
      '#type' => 'select',
      '#title' => $this->t('Date only format'),
      '#description' => $this->t('A date format excluding the time part. This is used when the "all day" option is active.'),
      '#options' => $options,
      '#default_value' => $this->getSetting('date_only_format'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function formatDate($date, $all_day = FALSE) {
    $format_setting_name = $all_day ? 'date_only_format' : 'format_type';
    $format_type = $this->getSetting($format_setting_name);
    $timezone = $this->getSetting('timezone_override') ?: $date->getTimezone()->getName();
    return $this->dateFormatter->format($date->getTimestamp(), $format_type, '', $timezone != '' ? $timezone : NULL);
  }

}
