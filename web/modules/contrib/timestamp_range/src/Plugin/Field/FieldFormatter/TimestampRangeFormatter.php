<?php

namespace Drupal\timestamp_range\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\TimestampFormatter;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'timestamp range' formatter.
 *
 * @FieldFormatter(
 *   id = "timestamp_range",
 *   label = @Translation("Timestamp range"),
 *   field_types = {
 *     "timestamp_range"
 *   }
 * )
 */
class TimestampRangeFormatter extends TimestampFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'separator' => '-',
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['separator'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Date separator'),
      '#description' => $this->t('The string to separate the start and end dates'),
      '#default_value' => $this->getSetting('separator'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    if ($separator = $this->getSetting('separator')) {
      $summary[] = $this->t('Separator: %separator', ['%separator' => $separator]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $separator = $this->getSetting('separator');

    $date_format = $this->getSetting('date_format');
    $custom_date_format = '';
    $timezone = $this->getSetting('timezone') ?: NULL;
    $langcode = NULL;

    // If an RFC2822 date format is requested, then the month and day have to
    // be in English. @see http://www.faqs.org/rfcs/rfc2822.html
    if ($date_format === 'custom' && ($custom_date_format = $this->getSetting('custom_date_format')) === 'r') {
      $langcode = 'en';
    }

    foreach ($items as $delta => $item) {
      if ($item->value !== $item->end_value) {
        $elements[$delta] = [
          '#cache' => [
            'contexts' => [
              'timezone',
            ],
          ],
          'start_date' => [
            '#plain_text' => $this->dateFormatter->format($item->value, $date_format, $custom_date_format, $timezone, $langcode),
          ],
          'separator' => [
            '#plain_text' => ' ' . $separator . ' '
          ],
          'end_date' => [
            '#plain_text' => $this->dateFormatter->format($item->end_value, $date_format, $custom_date_format, $timezone, $langcode),
          ],
        ];
      }
      else {
        $elements[$delta] = [
          '#cache' => [
            'contexts' => [
              'timezone',
            ],
          ],
          '#markup' => $this->dateFormatter->format($item->value, $date_format, $custom_date_format, $timezone, $langcode),
        ];
      }
    }

    return $elements;
  }

}
