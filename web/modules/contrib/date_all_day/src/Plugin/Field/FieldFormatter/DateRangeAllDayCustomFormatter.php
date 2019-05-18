<?php

namespace Drupal\date_all_day\Plugin\Field\FieldFormatter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\date_all_day\DateRangeAllDayTrait;
use Drupal\datetime_range\Plugin\Field\FieldFormatter\DateRangeCustomFormatter;

/**
 * Plugin implementation of the 'Custom' formatter for 'daterange' fields.
 *
 * This formatter renders the data range as plain text, with a fully
 * configurable date format using the PHP date syntax and separator.
 *
 * @FieldFormatter(
 *   id = "daterange_all_day_custom",
 *   label = @Translation("Custom"),
 *   field_types = {
 *     "daterange_all_day"
 *   }
 * )
 */
class DateRangeAllDayCustomFormatter extends DateRangeCustomFormatter {

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

    $form['date_only_format'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Date only format'),
      '#description' => $this->t('A date format excluding the time part. This is used when the "all day" option is active. See <a href="http://php.net/manual/function.date.php" target="_blank">the documentation for PHP date formats</a>.'),
      '#default_value' => $this->getSetting('date_only_format'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function formatDate($date, $all_day = FALSE) {
    $format_setting_name = $all_day ? 'date_only_format' : 'date_format';
    $format = $this->getSetting($format_setting_name);
    $timezone = $this->getSetting('timezone_override');
    return $this->dateFormatter->format($date->getTimestamp(), 'custom', $format, $timezone != '' ? $timezone : NULL);
  }
}
