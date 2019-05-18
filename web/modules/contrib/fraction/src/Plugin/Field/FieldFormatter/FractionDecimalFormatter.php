<?php

namespace Drupal\fraction\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'fraction_decimal' formatter.
 *
 * @FieldFormatter(
 *   id = "fraction_decimal",
 *   label = @Translation("Decimal"),
 *   field_types = {
 *     "fraction"
 *   }
 * )
 */
class FractionDecimalFormatter extends FractionFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'precision' => 0,
      'auto_precision' => TRUE,
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    // Decimal precision.
    $elements['precision'] = array(
      '#type' => 'textfield',
      '#title' => t('Decimal precision'),
      '#description' => t('Specify the number of digits after the decimal place to display. When "Auto precision" is enabled, this value essentially becomes a minimum fallback precision.'),
      '#default_value' => $this->getSetting('precision'),
      '#required' => TRUE,
      '#weight' => 0,
    );

    // Auto precision.
    $elements['auto_precision'] = array(
      '#type' => 'checkbox',
      '#title' => t('Auto precision'),
      '#description' => t('Automatically determine the maximum precision if the fraction has a base-10 denominator. For example, 1/100 would have a precision of 2, 1/1000 would have a precision of 3, etc.'),
      '#default_value' => $this->getSetting('auto_precision'),
      '#weight' => 1,
    );

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();

    // Summarize the precision setting.
    $precision = $this->getSetting('precision');
    $auto_precision = !empty($this->getSetting('auto_precision')) ? 'On' : 'Off';
    $summary[] = t('Precision: @precision, Auto-precision: @auto_precision', array(
      '@precision' => $precision,
      '@auto_precision' => $auto_precision,
    ));

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();

    // Load the precision setting.
    $precision = $this->getSetting('precision');

    // Iterate through the items.
    foreach ($items as $delta => $item) {

      // Output fraction as a decimal with a fixed or automatic precision.
      $auto_precision = !empty($this->getSetting('auto_precision')) ? TRUE : FALSE;
      $elements[$delta] = array(
        '#markup' => $item->fraction->toDecimal($precision, $auto_precision),
      );
    }

    return $elements;
  }
}
