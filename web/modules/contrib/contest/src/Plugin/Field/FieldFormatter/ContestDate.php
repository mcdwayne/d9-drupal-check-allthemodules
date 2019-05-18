<?php

namespace Drupal\contest\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\NumericFormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'contest_date' formatter.
 *
 * Convert unixtime to contest date format.
 *
 * @FieldFormatter(
 *   id = "contest_date",
 *   label = @Translation("Contest Date"),
 *   field_types = {
 *     "integer"
 *   }
 * )
 */
class ContestDate extends NumericFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'format'        => 'contest_date',
      'custom_format' => 'F j, Y',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  protected function numberFormat($number) {
    $dateFrmt = \Drupal::service('date.formatter');

    if (!is_numeric($number)) {
      return '';
    }
    if ($this->getSetting('format') == 'custom' && $this->getSetting('custom_format')) {
      return $dateFrmt->format($number, 'custom', $this->getSetting('custom_format'));
    }
    return $dateFrmt->format($number, 'contest_date');
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements['format'] = [
      '#title'         => t('Date Format'),
      '#type'          => 'select',
      '#default_value' => $this->getSetting('format') ? $this->getSetting('format') : 'contest_date',
      '#options'       => [
        'contest' => $this->t('Contest'),
        'custom'  => $this->t('Custom'),
      ],
      '#weight'        => 0,
    ];
    $elements['custom_format'] = [
      '#title'         => $this->t('Custom Date Format'),
      '#type'          => 'textfield',
      '#default_value' => $this->getSetting('custom_format'),
      '#maxlength'     => 30,
      '#width'         => 30,
      '#weight'        => 10,
    ];
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary[] = $this->numberFormat(REQUEST_TIME);

    if ($this->getSetting('format') == 'custom' && $this->getSetting('custom_format')) {
      $summary[] = $this->t('Custom Format: @format', ['@format' => $this->getSetting('custom_format')]);
    }
    else {
      $summary[] = $this->t('Default Contest Format');
    }
    return $summary;
  }

}
