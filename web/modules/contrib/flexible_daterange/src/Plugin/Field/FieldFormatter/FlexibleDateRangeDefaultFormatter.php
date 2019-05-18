<?php

namespace Drupal\flexible_daterange\Plugin\Field\FieldFormatter;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\datetime_range\Plugin\Field\FieldFormatter\DateRangeDefaultFormatter;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'Flexible daterange' default field formatter.
 *
 * @FieldFormatter(
 *   id = "flexible_daterange_default",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "flexible_daterange"
 *   }
 * )
 */
class FlexibleDateRangeDefaultFormatter extends DateRangeDefaultFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'format_type_hide_time' => 'medium',
    ] + parent::defaultSettings();
  }

  /**
   * Format date according to the selected option while hiding timestamps.
   */
  public function formatHideTimeDate($date) {
    $format_type = $this->getSetting('format_type_hide_time');
    $timezone = $this->getSetting('timezone_override') ?: $date->getTimezone()->getName();
    return $this->dateFormatter->format($date->getTimestamp(), $format_type, '', $timezone != '' ? $timezone : NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function buildHideTimeDateWithIsoAttribute(DrupalDateTime $date) {
    $build = parent::buildDateWithIsoAttribute($date);
    $build['#text'] = $this->formatHideTimeDate($date);
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $separator = $this->getSetting('separator');

    foreach ($items as $delta => $item) {
      if (!empty($item->start_date) && !empty($item->end_date)) {
        /** @var \Drupal\Core\Datetime\DrupalDateTime $start_date */
        $start_date = $item->start_date;
        /** @var \Drupal\Core\Datetime\DrupalDateTime $end_date */
        $end_date = $item->end_date;

        $hide_time = $item->hide_time;
        $start_date_string = $hide_time == 1 ? $this->buildHideTimeDateWithIsoAttribute($start_date) : $this->buildDateWithIsoAttribute($start_date);
        $end_date_string = $hide_time == 1 ? $this->buildHideTimeDateWithIsoAttribute($end_date) : $this->buildDateWithIsoAttribute($end_date);
        if ($start_date->getTimestamp() !== $end_date->getTimestamp()) {
          $elements[$delta] = [
            'start_date' => $start_date_string,
            'separator' => ['#plain_text' => ' ' . $separator . ' '],
            'end_date' => $end_date_string,
          ];
        }
        else {
          $elements[$delta] = $start_date_string;

          if (!empty($item->_attributes)) {
            $elements[$delta]['#attributes'] += $item->_attributes;
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
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    $form['format_type_hide_time'] = [
      '#type' => 'select',
      '#title' => 'Hide time date format',
      '#description' => $this->t("Choose a format for displaying the date when the hide time option is enabled."),
      '#options' => $form['format_type']['#options'],
      '#default_value' => $this->getSetting('format_type_hide_time'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $date = new DrupalDateTime();
    $summary[] = $this->t('Hide time format: @display', ['@display' => $this->formatHideTimeDate($date)]);

    return $summary;
  }

}
