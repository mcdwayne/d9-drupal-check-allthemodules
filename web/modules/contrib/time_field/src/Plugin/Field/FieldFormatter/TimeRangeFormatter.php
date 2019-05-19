<?php

namespace Drupal\time_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\time_field\Time;

/**
 * Plugin implementation of the 'time_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "time_range_formatter",
 *   label = @Translation("Time range formatter"),
 *   field_types = {
 *     "time_range"
 *   }
 * )
 */
class TimeRangeFormatter extends FormatterBase {

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
    $from = Time::createFromTimestamp($item->from);
    $to = Time::createFromTimestamp($item->to);
    $time_format = $this->getSetting('time_format');
    $timerange_format = $this->getSetting('timerange_format');
    $timerange_format = str_replace('start', $from->format($time_format), $timerange_format);
    $timerange_format = str_replace('end', $to->format($time_format), $timerange_format);
    return $timerange_format;
  }

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
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['timerange_format'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Time Range Format'),
      '#default_value' => (string) $this->getSetting('timerange_format'),
      '#description' => $this->t('This setting must have `start` and `end` keys'),
    ];

    $elements['time_format'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Time Format'),
      '#default_value' => $this->getSetting('time_format'),
      '#description' => $this->getTimeDescription(),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'time_format' => 'h:i a',
      'timerange_format' => 'start ~ end',
    ] + parent::defaultSettings();
  }

  /**
   * Description of the time field.
   *
   * @return string
   *   Description of the time field.
   */
  private function getTimeDescription() {
    $output = '';
    $output .= $this->t('a - Lowercase am or pm') . '<br/>';
    $output .= $this->t('A - Uppercase AM or PM') . '<br/>';
    $output .= $this->t('B - Swatch Internet time (000 to 999)') . '<br/>';
    $output .= $this->t('g - 12-hour format of an hour (1 to 12)') . '<br/>';
    $output .= $this->t('G - 24-hour format of an hour (0 to 23)') . '<br/>';
    $output .= $this->t('h - 12-hour format of an hour (01 to 12)') . '<br/>';
    $output .= $this->t('H - 24-hour format of an hour (00 to 23)') . '<br/>';
    $output .= $this->t('i - Minutes with leading zeros (00 to 59)') . '<br/>';
    $output .= $this->t('s - Seconds, with leading zeros (00 to 59)') . '<br/>';
    return $output;
  }

}
