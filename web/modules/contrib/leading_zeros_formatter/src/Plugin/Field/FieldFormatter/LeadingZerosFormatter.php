<?php

namespace Drupal\leading_zeros_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\IntegerFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'leading_zeros_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "leading_zeros_formatter",
 *   label = @Translation("Leading Zeros"),
 *   field_types = {
 *     "integer"
 *   }
 * )
 */
class LeadingZerosFormatter extends IntegerFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'min_length' => 1,
      ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['min_length'] = array(
      '#title' => $this->t('Minimum length'),
      '#type' => 'number',
      '#default_value' => $this->getSetting('min_length'),
      '#min' => 1,
      '#max' => 19,
    );

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $min_length = $this->getSetting('min_length');
    $summary[] = t('Minimum length: @length', array('@length' => $min_length));

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  protected function numberFormat($number) {
    $min_length = $this->getSetting('min_length');

    // function padAndFormat: https://stackoverflow.com/a/19361915
    if(strlen($number) >= $min_length) {
      $number = '1'.$number;
    }
    else {
      $number = '1'.str_pad($number, $min_length-1, '0', STR_PAD_LEFT);
    }
    $number = number_format($number, 0, '', $this->getSetting('thousand_separator'));
    $number[0] = '0';

    return $number;
  }

}